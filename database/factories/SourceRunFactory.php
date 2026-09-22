<?php

namespace Database\Factories;

use App\Models\Source;
use App\Models\SourceRun;
use App\SourceRunStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SourceRun>
 */
class SourceRunFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'source_id' => Source::factory(),
            'status' => SourceRunStatus::Succeeded,
            'started_at' => now()->subMinute(),
            'finished_at' => now(),
            'items_found' => 0,
            'items_created' => 0,
            'items_skipped' => 0,
            'error_message' => null,
            'metadata' => null,
        ];
    }
}
