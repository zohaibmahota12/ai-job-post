<?php

namespace Database\Seeders;

use App\Models\Source;
use Illuminate\Database\Seeder;

class SourceSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Source::query()->updateOrCreate(
            ['driver' => 'agent_reach'],
            [
                'name' => 'Agent Reach',
                'description' => 'Internet access layer for later collection. Phase 1 does not call Agent Reach. See docs/AGENT_REACH.md.',
                'is_enabled' => false,
                'config' => null,
            ],
        );
    }
}
