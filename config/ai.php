<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI proposal generation
    |--------------------------------------------------------------------------
    |
    | Proposal generation is optional. When disabled, discovery, matching,
    | applications, and manual proposal editing continue to work.
    |
    */

    'enabled' => (bool) env('AI_ENABLED', false),

    'provider' => env('AI_PROVIDER', 'openai'),

    'timeout' => (float) env('AI_TIMEOUT', 30),

    'connect_timeout' => (float) env('AI_CONNECT_TIMEOUT', 5),

    'max_tokens' => (int) env('AI_MAX_TOKENS', 1200),

    /*
    |--------------------------------------------------------------------------
    | Abuse protection
    |--------------------------------------------------------------------------
    |
    | Authenticated users may generate or regenerate this many proposals
    | per minute. This is not billing — only a simple rate limit.
    |
    */

    'rate_limit_per_minute' => (int) env('AI_RATE_LIMIT_PER_MINUTE', 5),

    'providers' => [
        'openai' => [
            'api_key' => env('AI_API_KEY'),
            'model' => env('AI_MODEL', 'gpt-4o-mini'),
            'base_url' => env('AI_BASE_URL', 'https://api.openai.com/v1'),
        ],
    ],

];
