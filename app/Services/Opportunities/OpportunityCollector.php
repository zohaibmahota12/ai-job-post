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
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

class OpportunityCollector
{
    public function __construct(
        private SourceManager $sources,
        private OpportunityNormalizer $normalizer,
        private OpportunityDeduplicator $deduplicator,
        private SkillAttacher $skills,
        private MatchSynchronizer $matches,
    ) {}

    /**
     * @return Collection<int, SourceRun>
     */
    public function collect(?string $sourceKeyOrDriver = null): Collection
    {
        $sources = Source::query()
            ->when($sourceKeyOrDriver !== null, function ($query) use ($sourceKeyOrDriver) {
                $query->where(function ($inner) use ($sourceKeyOrDriver) {
                    $inner->where('key', $sourceKeyOrDriver)
                        ->orWhere('driver', $sourceKeyOrDriver);
                });
            })
            ->orderBy('id')
            ->get();

        return $sources->map(fn (Source $source): SourceRun => $this->collectSource($source));
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
            || $existing->required_experience_years !== $normalized->requiredExperienceYears;
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
            'error_message' => $error,
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
        $source->forceFill([
            'last_run_at' => $run->finished_at ?? now(),
            'last_success_at' => $run->status === SourceRunStatus::Succeeded
                ? ($run->finished_at ?? now())
                : $source->last_success_at,
            'last_error' => $run->status === SourceRunStatus::Failed
                ? $run->error_message
                : ($run->status === SourceRunStatus::Succeeded ? null : $source->last_error),
        ])->save();
    }

    private function recordError(SourceRun $run, Source $source, Throwable $exception, string $level): void
    {
        SystemError::query()->create([
            'level' => $level,
            'message' => $exception->getMessage(),
            'context' => [
                'source' => $source->key ?? $source->driver,
                'driver' => $source->driver,
                'exception' => $exception::class,
            ],
            'source_run_id' => $run->id,
            'occurred_at' => now(),
        ]);

        Log::log($level, $exception->getMessage(), [
            'source' => $source->key ?? $source->driver,
            'source_run_id' => $run->id,
        ]);
    }
}
