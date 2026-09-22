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
            ['key' => 'agent_reach'],
            [
                'driver' => 'agent_reach',
                'type' => 'agent_reach',
                'name' => 'Agent Reach',
                'description' => 'Optional external integration layer only. Not part of the core collection pipeline. See docs/AGENT_REACH.md.',
                'is_enabled' => false,
                'config' => null,
            ],
        );

        Source::query()->updateOrCreate(
            ['key' => 'rss_template'],
            [
                'driver' => 'rss',
                'type' => 'rss',
                'name' => 'RSS / Atom feed',
                'description' => 'Public RSS or Atom feed adapter. Set config.url to a permitted public feed, then enable the source. Disabled by default.',
                'is_enabled' => false,
                'config' => [
                    'url' => null,
                    'rate_limit_hint' => 'Respect the publisher. Prefer daily cPanel cron collection.',
                ],
            ],
        );

        Source::query()->updateOrCreate(
            ['key' => 'json_api_template'],
            [
                'driver' => 'json_api',
                'type' => 'json_api',
                'name' => 'Public JSON job API',
                'description' => 'Generic public JSON API adapter template. Configure url, optional items_path, and field_map. No commercial provider is claimed as supported until configured and tested. Disabled by default.',
                'is_enabled' => false,
                'config' => [
                    'url' => null,
                    'items_path' => 'jobs',
                    'field_map' => [
                        'title' => 'title',
                        'description' => 'description',
                        'company' => 'company',
                        'source_url' => 'url',
                        'external_id' => 'id',
                        'location' => 'location',
                        'job_type' => 'job_type',
                        'workplace' => 'workplace',
                        'budget_min' => 'budget_min',
                        'budget_max' => 'budget_max',
                        'currency' => 'currency',
                        'posted_at' => 'posted_at',
                        'skills' => 'skills',
                    ],
                    'rate_limit_hint' => 'Only credential-free public endpoints. Do not store API secrets in config.',
                ],
            ],
        );
    }
}
