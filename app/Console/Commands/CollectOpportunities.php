<?php

namespace App\Console\Commands;

use App\Services\Opportunities\OpportunityCollector;
use App\SourceRunStatus;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('opportunities:collect {--source= : Limit collection to one source driver}')]
#[Description('Collect opportunities from enabled sources')]
class CollectOpportunities extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(OpportunityCollector $collector): int
    {
        $driver = $this->option('source');
        $driver = is_string($driver) && $driver !== '' ? $driver : null;

        $runs = $collector->collect($driver);

        if ($runs->isEmpty()) {
            $this->warn('No sources are configured.');

            return self::SUCCESS;
        }

        $failed = false;

        foreach ($runs as $run) {
            $sourceName = $run->source?->name ?? 'Source';
            $line = $sourceName.': '.$run->status->value;

            if ($run->status === SourceRunStatus::Failed) {
                $failed = true;
                $this->error($line.($run->error_message ? ' — '.$run->error_message : ''));

                continue;
            }

            $this->line($line);
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
