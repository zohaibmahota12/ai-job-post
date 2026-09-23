<?php

namespace App\Console\Commands;

use App\Services\Opportunities\OpportunityCollector;
use App\SourceRunStatus;
use App\Support\SecretRedactor;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use RuntimeException;
use Throwable;

#[Signature('opportunities:collect {--source= : Limit collection to one source key or driver}')]
#[Description('Collect opportunities from enabled sources')]
class CollectOpportunities extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(OpportunityCollector $collector, SecretRedactor $redactor): int
    {
        $driver = $this->option('source');
        $driver = is_string($driver) && $driver !== '' ? $driver : null;

        try {
            $runs = $collector->collect($driver);
        } catch (RuntimeException $exception) {
            $this->error($redactor->redact($exception->getMessage()) ?? $exception->getMessage());

            return self::FAILURE;
        } catch (Throwable $exception) {
            $this->error($redactor->redact($exception->getMessage()) ?? $exception->getMessage());

            return self::FAILURE;
        }

        if ($runs->isEmpty()) {
            $this->warn('No sources are configured.');

            return self::SUCCESS;
        }

        $created = 0;
        $updated = 0;
        $duplicated = 0;
        $failedSources = 0;

        $this->newLine();
        $this->info('Opportunity collection completed.');
        $this->newLine();
        $this->line('Sources:');

        foreach ($runs as $run) {
            $sourceName = $run->source?->name ?? 'Source';
            $padded = str_pad($sourceName, 24);

            if ($run->status === SourceRunStatus::Failed) {
                $failedSources++;
                $message = $redactor->redact($run->error_message) ?? '';
                $this->error($padded.'FAILED'.($message !== '' ? ' — '.$message : ''));

                continue;
            }

            if ($run->status === SourceRunStatus::Succeeded) {
                $created += $run->items_created;
                $updated += $run->items_updated;
                $duplicated += $run->items_duplicated;
                $this->line($padded.'SUCCESS');

                continue;
            }

            $this->line($padded.strtoupper($run->status->value));
        }

        $this->newLine();
        $this->line('Created: '.$created);
        $this->line('Updated: '.$updated);
        $this->line('Duplicated: '.$duplicated);
        $this->line('Failed sources: '.$failedSources);

        return $failedSources > 0 ? self::FAILURE : self::SUCCESS;
    }
}
