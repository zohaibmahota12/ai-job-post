<?php

namespace App\Services\Opportunities;

use App\Models\Opportunity;
use App\Models\Source;
use App\Models\SourceRun;
use App\Models\SystemError;
use App\Services\Skills\SkillAttacher;
use App\SourceRunStatus;
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
    ) {}

    /**
     * Matching is intentionally not invoked here. Scoring and persisted matches belong to later phases.
     *
     * @return Collection<int, SourceRun>
     */
    public function collect(?string $driver = null): Collection
    {
        $sources = Source::query()
            ->when($driver !== null, fn ($query) => $query->where('driver', $driver))
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
            return $this->finish($run, SourceRunStatus::Skipped, error: 'Source is disabled.');
        }

        try {
            $result = $this->sources->adapterFor($source)->collect($source);
            $counts = $this->store($source, $run, $result->opportunities);

            return $this->finish($run, SourceRunStatus::Succeeded, counts: $counts);
        } catch (SourceCollectionException $exception) {
            return $this->fail($run, $source, $exception, 'warning');
        } catch (Throwable $exception) {
            return $this->fail($run, $source, $exception, 'error');
        }
    }

    /**
     * @param  list<RawOpportunity>  $rawOpportunities
     * @return array{found: int, created: int, skipped: int}
     */
    private function store(Source $source, SourceRun $run, array $rawOpportunities): array
    {
        $created = 0;
        $skipped = 0;

        foreach ($rawOpportunities as $raw) {
            try {
                $normalized = $this->normalizer->normalize($raw, $source);

                if ($this->deduplicator->findExisting($normalized) !== null) {
                    $skipped++;

                    continue;
                }

                DB::transaction(function () use ($normalized, $source): void {
                    $opportunity = Opportunity::query()->create([
                        'source_id' => $source->id,
                        'title' => $normalized->title,
                        'description' => $normalized->description,
                        'company' => $normalized->company,
                        'source_url' => $normalized->sourceUrl,
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
                    ]);

                    $this->skills->attachNamesToOpportunity($opportunity, $normalized->skillNames);
                });

                $created++;
            } catch (UniqueConstraintViolationException) {
                $skipped++;
            } catch (InvalidArgumentException $exception) {
                $skipped++;
                $this->recordError($run, $source, $exception, 'warning');
            }
        }

        return [
            'found' => count($rawOpportunities),
            'created' => $created,
            'skipped' => $skipped,
        ];
    }

    /**
     * @param  array{found: int, created: int, skipped: int}|null  $counts
     */
    private function finish(SourceRun $run, SourceRunStatus $status, ?string $error = null, ?array $counts = null): SourceRun
    {
        $run->forceFill([
            'status' => $status,
            'finished_at' => now(),
            'items_found' => $counts['found'] ?? 0,
            'items_created' => $counts['created'] ?? 0,
            'items_skipped' => $counts['skipped'] ?? 0,
            'error_message' => $error,
        ])->save();

        return $run->refresh();
    }

    private function fail(SourceRun $run, Source $source, Throwable $exception, string $level): SourceRun
    {
        $this->recordError($run, $source, $exception, $level);

        return $this->finish($run, SourceRunStatus::Failed, error: $exception->getMessage());
    }

    private function recordError(SourceRun $run, Source $source, Throwable $exception, string $level): void
    {
        SystemError::query()->create([
            'level' => $level,
            'message' => $exception->getMessage(),
            'context' => [
                'source' => $source->driver,
                'exception' => $exception::class,
            ],
            'source_run_id' => $run->id,
            'occurred_at' => now(),
        ]);

        Log::log($level, $exception->getMessage(), [
            'source' => $source->driver,
            'source_run_id' => $run->id,
        ]);
    }
}
