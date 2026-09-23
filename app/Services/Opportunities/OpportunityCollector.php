<?php

namespace App\Services\Opportunities;

use App\Models\Opportunity;
use App\Models\Source;
use App\Models\SourceRun;
use App\Models\SystemError;
use App\Services\Matching\MatchSynchronizer;
use App\Services\Skills\SkillAttacher;
use App\SourceRunStatus;
use App\Sources\NormalizedOpportunity;
use App\Sources\RawOpportunity;
use App\Sources\SourceCollectionException;
use App\Sources\SourceManager;
use App\Support\SecretRedactor;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class OpportunityCollector
{
    public function __construct(
        private SourceManager $sources,
        private OpportunityNormalizer $normalizer,
        private OpportunityDeduplicator $deduplicator,
        private SkillAttacher $skills,
        private MatchSynchronizer $matches,
        private OpportunityLifecycle $lifecycle,
        private SecretRedactor $redactor,
    ) {}

    /**
     * @return Collection<int, SourceRun>
     */
    public function collect(?string $sourceKeyOrDriver = null): Collection
    {
        $lockSeconds = max(60, (int) config('opportunity.collection.lock_seconds', 900));
        $lock = Cache::lock('opportunities:collect', $lockSeconds);

        if (! $lock->get()) {
            throw new RuntimeException('Opportunity collection is already running.');
        }

        try {
            $sources = Source::query()
                ->when($sourceKeyOrDriver !== null, function ($query) use ($sourceKeyOrDriver) {
                    $query->where(function ($inner) use ($sourceKeyOrDriver) {
                        $inner->where('key', $sourceKeyOrDriver)
                            ->orWhere('driver', $sourceKeyOrDriver);
                    });
                })
                ->orderBy('id')
                ->get();

            $runs = $sources->map(fn (Source $source): SourceRun => $this->collectSource($source));

            $this->lifecycle->expirePastDeadlines();

            return $runs;
        } finally {
            $lock->release();
        }
    }

    public function collectSource(Source $source): SourceRun
    {
        $run = $source->runs()->create([
            'status' => SourceRunStatus::Running,
            'started_at' => now(),
        ]);

        if (! $source->is_enabled) {
            $finished = $this->finish($run, SourceRunStatus::Skipped, error: 'Source is disabled.');
            $this->touchSource($source, $finished);

            return $finished;
        }

        try {
            $result = $this->sources->adapterFor($source)->collect($source);
            [$counts, $touched] = $this->store($source, $run, $result->opportunities);

            if ($touched !== []) {
                $this->matches->syncOpportunities($touched);
            }

            $finished = $this->finish($run, SourceRunStatus::Succeeded, counts: $counts);
            $this->touchSource($source, $finished);

            return $finished;
        } catch (SourceCollectionException $exception) {
            $finished = $this->fail($run, $source, $exception, 'warning');
            $this->touchSource($source, $finished);

            return $finished;
        } catch (Throwable $exception) {
            $finished = $this->fail($run, $source, $exception, 'error');
            $this->touchSource($source, $finished);

            return $finished;
        }
    }

    /**
     * @param  list<RawOpportunity>  $rawOpportunities
     * @return array{0: array{found: int, created: int, updated: int, skipped: int, duplicated: int}, 1: list<Opportunity>}
     */
    private function store(Source $source, SourceRun $run, array $rawOpportunities): array
    {
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $duplicated = 0;
        $touched = [];

        foreach ($rawOpportunities as $raw) {
            try {
                $normalized = $this->normalizer->normalize($raw, $source);
                $existing = $this->deduplicator->findExisting($normalized);

                if ($existing !== null) {
                    $sameSourceIdentity = $existing->source_id === $source->id
                        && $normalized->externalId !== null
                        && $existing->external_id === $normalized->externalId;

                    $sameCanonical = $normalized->canonicalUrl !== ''
                        && $existing->canonical_url === $normalized->canonicalUrl;

                    $sameFingerprint = $existing->content_hash === $this->deduplicator->fingerprint($normalized);

                    if ($sameSourceIdentity || $sameCanonical || $sameFingerprint) {
                        if ($this->shouldUpdate($existing, $normalized)) {
                            $this->persistExisting($existing, $normalized, $source);
                            $updated++;
                            $touched[] = $existing->fresh(['skills']);
                        } else {
                            $duplicated++;
                            $skipped++;
                        }

                        continue;
                    }
                }

                $opportunity = DB::transaction(function () use ($normalized, $source): Opportunity {
                    $opportunity = Opportunity::query()->create($this->attributes($normalized, $source));
                    $this->skills->attachNamesToOpportunity($opportunity, $normalized->skillNames);

                    return $opportunity->load('skills');
                });

                $created++;
                $touched[] = $opportunity;
            } catch (UniqueConstraintViolationException) {
                $duplicated++;
                $skipped++;
            } catch (InvalidArgumentException $exception) {
                $skipped++;
                $this->recordError($run, $source, $exception, 'warning');
            }
        }

        return [
            [
                'found' => count($rawOpportunities),
                'created' => $created,
                'updated' => $updated,
                'skipped' => $skipped,
                'duplicated' => $duplicated,
            ],
            $touched,
        ];
    }

    private function shouldUpdate(Opportunity $existing, NormalizedOpportunity $normalized): bool
    {
        return $existing->title !== $normalized->title
            || $existing->description !== $normalized->description
            || $existing->company !== $normalized->company
            || $existing->source_url !== $normalized->sourceUrl
            || $existing->location !== $normalized->location
            || $existing->job_type?->value !== $normalized->jobType?->value
            || $existing->workplace?->value !== $normalized->workplace?->value
            || (string) $existing->budget_min !== (string) ($normalized->budgetMin ?? '')
            || (string) $existing->budget_max !== (string) ($normalized->budgetMax ?? '')
            || $existing->currency !== $normalized->currency
            || optional($existing->posted_at)?->toDateTimeString() !== $normalized->postedAt
            || optional($existing->deadline_at)?->toDateTimeString() !== $normalized->deadlineAt
            || $existing->required_experience_years !== $normalized->requiredExperienceYears
            || $existing->status !== $normalized->status;
    }

    private function persistExisting(Opportunity $existing, NormalizedOpportunity $normalized, Source $source): void
    {
        DB::transaction(function () use ($existing, $normalized, $source): void {
            $existing->fill($this->attributes($normalized, $source))->save();
            $this->skills->attachNamesToOpportunity($existing, $normalized->skillNames);
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function attributes(NormalizedOpportunity $normalized, Source $source): array
    {
        return [
            'source_id' => $source->id,
            'title' => $normalized->title,
            'description' => $normalized->description,
            'company' => $normalized->company,
            'source_url' => $normalized->sourceUrl,
            'canonical_url' => $normalized->canonicalUrl !== '' ? $normalized->canonicalUrl : null,
            'external_id' => $normalized->externalId,
            'location' => $normalized->location,
            'job_type' => $normalized->jobType,
            'workplace' => $normalized->workplace,
            'budget_min' => $normalized->budgetMin,
            'budget_max' => $normalized->budgetMax,
            'currency' => $normalized->currency,
            'posted_at' => $normalized->postedAt,
            'deadline_at' => $normalized->deadlineAt,
            'required_experience_years' => $normalized->requiredExperienceYears,
            'raw_data' => $normalized->raw,
            'normalized_data' => $normalized->toArray(),
            'status' => $normalized->status,
            'content_hash' => $this->deduplicator->fingerprint($normalized),
        ];
    }

    /**
     * @param  array{found: int, created: int, updated: int, skipped: int, duplicated: int}|null  $counts
     */
    private function finish(SourceRun $run, SourceRunStatus $status, ?string $error = null, ?array $counts = null): SourceRun
    {
        $run->forceFill([
            'status' => $status,
            'finished_at' => now(),
            'items_found' => $counts['found'] ?? 0,
            'items_created' => $counts['created'] ?? 0,
            'items_updated' => $counts['updated'] ?? 0,
            'items_skipped' => $counts['skipped'] ?? 0,
            'items_duplicated' => $counts['duplicated'] ?? 0,
            'error_message' => $this->redactor->redact($error),
        ])->save();

        return $run->refresh();
    }

    private function fail(SourceRun $run, Source $source, Throwable $exception, string $level): SourceRun
    {
        $this->recordError($run, $source, $exception, $level);

        return $this->finish($run, SourceRunStatus::Failed, error: $exception->getMessage());
    }

    private function touchSource(Source $source, SourceRun $run): void
    {
        $durationMs = null;

        if ($run->started_at !== null && $run->finished_at !== null) {
            $durationMs = (int) max(0, $run->started_at->diffInMilliseconds($run->finished_at));
        }

        $payload = [
            'last_run_at' => $run->finished_at ?? now(),
            'last_duration_ms' => $durationMs,
        ];

        if ($run->status === SourceRunStatus::Succeeded) {
            $payload['last_success_at'] = $run->finished_at ?? now();
            $payload['last_error'] = null;
            $payload['consecutive_failures'] = 0;
            $payload['last_item_count'] = $run->items_found;
            $payload['last_created_count'] = $run->items_created;
            $payload['last_updated_count'] = $run->items_updated;
            $payload['last_duplicated_count'] = $run->items_duplicated;
        } elseif ($run->status === SourceRunStatus::Failed) {
            $payload['last_error'] = $run->error_message;
            $payload['last_failure_at'] = $run->finished_at ?? now();
            $payload['consecutive_failures'] = ((int) $source->consecutive_failures) + 1;
        }

        $source->forceFill($payload)->save();
    }

    private function recordError(SourceRun $run, Source $source, Throwable $exception, string $level): void
    {
        $message = $this->redactor->redact($exception->getMessage()) ?? $exception->getMessage();

        SystemError::query()->create([
            'level' => $level,
            'message' => $message,
            'context' => [
                'source' => $source->key ?? $source->driver,
                'driver' => $source->driver,
                'exception' => $exception::class,
            ],
            'source_run_id' => $run->id,
            'occurred_at' => now(),
        ]);

        Log::log($level, $message, [
            'source' => $source->key ?? $source->driver,
            'source_run_id' => $run->id,
        ]);
    }
}
