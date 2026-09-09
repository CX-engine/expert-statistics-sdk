<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Expert Statistics API credentials
    |--------------------------------------------------------------------------
    |
    | Reuses the same env vars already configured for the
    | cx-engine/expert-stats-api-sdk-php connector binding, so no new secrets
    | need to be provisioned in a host app that already talks to this API.
    */
    'api_url' => env('XPSTAT_API_URL', 'https://xp-stats-201.bluerock.tel/api'),
    'email' => env('XPSTAT_USERNAME'),
    'password' => env('XPSTAT_PASSWORD'),
    'timeout' => (int) env('XPSTAT_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Response cache TTL (seconds)
    |--------------------------------------------------------------------------
    */
    'cache_ttl' => (int) env('EXPERT_STATISTICS_CACHE_TTL', 300),

    /*
    |--------------------------------------------------------------------------
    | Permission gate
    |--------------------------------------------------------------------------
    |
    | Spatie permission string(s) checked before rendering any Dashboard /
    | Expert Statistics Livewire page. A user passes if they have any one of
    | these permissions.
    */
    'permissions' => [
        'expert-statistics.view',
        'expert-statistics.*',
    ],
];
