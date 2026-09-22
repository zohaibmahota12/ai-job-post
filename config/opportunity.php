<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Scheduled collection
    |--------------------------------------------------------------------------
    |
    | cPanel cron can call `php artisan opportunities:collect` directly.
    | When this flag is true, `php artisan schedule:run` also runs that
    | command once a day. It stays off until an operator enables it.
    |
    */

    'schedule_collection' => (bool) env('OPPORTUNITY_SCHEDULE_COLLECTION', false),

    /*
    |--------------------------------------------------------------------------
    | Outbound HTTP for source adapters
    |--------------------------------------------------------------------------
    */

    'http' => [
        'connect_timeout' => (float) env('OPPORTUNITY_HTTP_CONNECT_TIMEOUT', 5),
        'timeout' => (float) env('OPPORTUNITY_HTTP_TIMEOUT', 15),
        'max_redirects' => (int) env('OPPORTUNITY_HTTP_MAX_REDIRECTS', 3),
        'max_bytes' => (int) env('OPPORTUNITY_HTTP_MAX_BYTES', 2_000_000),
        'user_agent' => env('OPPORTUNITY_HTTP_USER_AGENT', 'OpportunityHunter/2.0 (+https://github.com/zohaibmahota12/ai-job-post)'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Deterministic match scoring
    |--------------------------------------------------------------------------
    |
    | Weights sum to 100. Unknown factors earn 0 points and are marked
    | unknown in the breakdown instead of inventing a partial score.
    |
    */

    'scoring' => [
        'weights' => [
            'skills' => 30,
            'keywords' => 20,
            'experience' => 15,
            'job_type' => 10,
            'workplace' => 10,
            'location' => 5,
            'budget' => 10,
        ],
        'dashboard_minimum' => (int) env('OPPORTUNITY_DASHBOARD_MIN_SCORE', 50),
    ],

];
