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
    | Permission gates
    |--------------------------------------------------------------------------
    |
    | Spatie permission string(s) checked before rendering an Expert
    | Statistics Livewire page. A user passes if they have any one of the
    | listed permissions.
    |
    | `permissions` gates every page (everything except the free-standing
    | Dashboard) - including `expert-statistics.modify`, since a user who
    | can edit configuration must also be able to view it.
    | `dashboard_permissions` gates only the Dashboard page, and deliberately
    | also accepts the broader `expert-statistics.*` permissions - a user
    | with full module access shouldn't lose the Dashboard just because they
    | were never explicitly granted `dashboard.*` too.
    | `modification_permissions` does NOT gate page access - the
    | Configuration pages (host selection, PBX settings) and the report
    | edit/delete actions are viewable by anyone who can view the module;
    | this key instead gates the actual write actions within those pages
    | (see AuthorizesExpertStatisticsModification and each component's own
    | canModify()/ensureCanModify() checks) - a client granted read-only
    | access can browse but not change anything, only
    | `expert-statistics.modify` (or full `*` access, which already implies
    | it) can.
    */
    'permissions' => [
        'expert-statistics.view',
        'expert-statistics.modify',
        'expert-statistics.*',
    ],

    'dashboard_permissions' => [
        'dashboard.view',
        'dashboard.*',
        'expert-statistics.view',
        'expert-statistics.*',
    ],

    'modification_permissions' => [
        'expert-statistics.modify',
        'expert-statistics.*',
    ],
];
