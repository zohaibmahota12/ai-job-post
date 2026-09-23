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
                'description' => 'Public RSS or Atom feed adapter template. Set config.url to a permitted public feed, then enable the source. Disabled by default.',
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
                'description' => 'Generic public JSON API adapter template. Configure url, optional items_path, and field_map. Disabled by default.',
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

        Source::query()->updateOrCreate(
            ['key' => 'remotive_remote_jobs'],
            [
                'driver' => 'json_api',
                'type' => 'json_api',
                'name' => 'Remotive',
                'description' => 'Public Remotive remote jobs API (https://remotive.com/api-documentation). Credit Remotive and link to original listings. Keep collection to about once per day.',
                'is_enabled' => false,
                'config' => [
                    'url' => 'https://remotive.com/api/remote-jobs',
                    'items_path' => 'jobs',
                    'field_map' => [
                        'title' => 'title',
                        'description' => 'description',
                        'company' => 'company_name',
                        'source_url' => 'url',
                        'external_id' => 'id',
                        'location' => 'candidate_required_location',
                        'job_type' => 'job_type',
                        'posted_at' => 'publication_date',
                        'skills' => 'tags',
                    ],
                    'rate_limit_hint' => 'Remotive documents roughly ≤4 requests/day and avoid >2/min. Daily cron is appropriate. Attribution required.',
                    'attribution' => 'Remotive',
                    'documentation_url' => 'https://remotive.com/api-documentation',
                ],
            ],
        );

        Source::query()->updateOrCreate(
            ['key' => 'remoteok_api'],
            [
                'driver' => 'json_api',
                'type' => 'json_api',
                'name' => 'RemoteOK',
                'description' => 'Public RemoteOK JSON API (https://remoteok.com/api). First array element is legal metadata and is skipped when it has no job title. Attribution required.',
                'is_enabled' => false,
                'config' => [
                    'url' => 'https://remoteok.com/api',
                    'items_path' => '',
                    'field_map' => [
                        'title' => 'position',
                        'description' => 'description',
                        'company' => 'company',
                        'source_url' => 'url',
                        'external_id' => 'id',
                        'location' => 'location',
                        'posted_at' => 'date',
                        'skills' => 'tags',
                    ],
                    'rate_limit_hint' => 'Use /api only. Prefer daily collection. Follow RemoteOK attribution guidance in the API legal object.',
                    'attribution' => 'RemoteOK',
                ],
            ],
        );

        Source::query()->updateOrCreate(
            ['key' => 'weworkremotely_programming_rss'],
            [
                'driver' => 'rss',
                'type' => 'rss',
                'name' => 'We Work Remotely (Programming)',
                'description' => 'Public We Work Remotely programming jobs RSS feed. Titles often use "Company: Role" format.',
                'is_enabled' => false,
                'config' => [
                    'url' => 'https://weworkremotely.com/categories/remote-programming-jobs.rss',
                    'rate_limit_hint' => 'Public RSS feed. Prefer daily collection and respect feed TTL.',
                    'attribution' => 'We Work Remotely',
                ],
            ],
        );

        Source::query()->updateOrCreate(
            ['key' => 'jobicy_remote_jobs'],
            [
                'driver' => 'json_api',
                'type' => 'json_api',
                'name' => 'Jobicy',
                'description' => 'Public Jobicy remote jobs API (https://jobicy.com/api/v2/remote-jobs). Credit Jobicy and use original job URLs.',
                'is_enabled' => false,
                'config' => [
                    'url' => 'https://jobicy.com/api/v2/remote-jobs',
                    'items_path' => 'jobs',
                    'field_map' => [
                        'title' => 'jobTitle',
                        'description' => 'jobDescription',
                        'company' => 'companyName',
                        'source_url' => 'url',
                        'external_id' => 'id',
                        'location' => 'jobGeo',
                        'job_type' => 'jobType',
                        'posted_at' => 'pubDate',
                        'budget_min' => 'salaryMin',
                        'budget_max' => 'salaryMax',
                        'currency' => 'salaryCurrency',
                        'listing_status' => 'listing_status',
                        'skills' => 'tags',
                    ],
                    'rate_limit_hint' => 'Public credential-free API. Prefer daily collection. Attribution requested by provider.',
                    'attribution' => 'Jobicy',
                    'documentation_url' => 'https://jobicy.com/jobs-api',
                ],
            ],
        );
    }
}
