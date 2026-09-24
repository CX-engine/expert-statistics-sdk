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

    /*
    |--------------------------------------------------------------------------
    | Documentation help panel
    |--------------------------------------------------------------------------
    |
    | Toggles the "Documentation" slide-over panel (Livewire\Docs\DocsHelperPanel)
    | included on every Expert Statistics page. Renders the Markdown guide
    | shipped in resources/docs/user/*.md - see CXEngine\ExpertStatistics\Support\DocsCatalog.
    */
    'docs_panel_enabled' => (bool) env('EXPERT_STATISTICS_DOCS_PANEL_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Docs assistant (AI chat, "how do I..." / "where do I find...")
    |--------------------------------------------------------------------------
    |
    | A separate "Ask AI" floating chat bubble (bottom-right, next to the
    | Documentation panel's own bottom-left bubble) - Livewire\Docs\DocsAssistantChat,
    | backed by Contracts\AnswersDocsQuestions => Services\PrismDocsAssistantResponder.
    | Only ever grounded in resources/docs/user/*.md - it never queries
    | ExpertStatisticsService, never sees call data, and never persists a
    | conversation (in-memory only, for the current page session). Disabled
    | by default: it costs real LLM tokens per message and needs a working
    | provider API key configured, unlike the plain Documentation panel.
    |
    | `provider`/`model` follow prism-php/prism's Provider enum values
    | (config/prism.php in the host app holds the actual API keys) -
    | defaults match this app's existing AI-feature convention
    | (Modules\Crm\Actions\CrmCall\AuditGrid\EvaluateCriteria).
    */
    'docs_assistant' => [
        'enabled' => (bool) env('EXPERT_STATISTICS_DOCS_ASSISTANT_ENABLED', false),
        'provider' => env('EXPERT_STATISTICS_DOCS_ASSISTANT_PROVIDER', 'mistral'),
        'model' => env('EXPERT_STATISTICS_DOCS_ASSISTANT_MODEL', 'mistral-small-latest'),
        'max_context_sections' => (int) env('EXPERT_STATISTICS_DOCS_ASSISTANT_MAX_SECTIONS', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | AI actions (create/schedule reports, create resource groups)
    |--------------------------------------------------------------------------
    |
    | A separate flag from `docs_assistant.enabled` above, on purpose: a site
    | can run the "Ask AI" bubble in plain Q&A mode (docs_assistant.enabled
    | only) without ever exposing mutation capability, until this is
    | explicitly turned on too. When false, Livewire\Docs\DocsAssistantChat
    | behaves exactly as it did before this feature existed — still calls
    | Contracts\AnswersDocsQuestions directly, never
    | Contracts\PerformsExpertStatisticsActions.
    |
    | Backed by Services\PrismActionAssistantResponder, which additionally
    | needs the active host's `ai_activated` flag set on the expert-stats
    | backend (Pbx3cxHost::isAiActivated()) before it can actually create
    | anything — a 403 from the backend surfaces as a friendly in-chat
    | message, not a crash, regardless of this flag.
    |
    | `max_tool_steps` bounds the read-only analytics-lookup tool-calling
    | loop (see PrismActionAssistantResponder::gather()) — how many times the
    | model may call ask_call_analytics in a single turn before it must
    | finalize its answer, to keep latency/cost bounded.
    */
    'ai_actions' => [
        'enabled' => (bool) env('EXPERT_STATISTICS_AI_ACTIONS_ENABLED', false),
        'provider' => env('EXPERT_STATISTICS_AI_ACTIONS_PROVIDER', 'mistral'),
        'model' => env('EXPERT_STATISTICS_AI_ACTIONS_MODEL', 'mistral-small-latest'),
        'max_tool_steps' => (int) env('EXPERT_STATISTICS_AI_ACTIONS_MAX_TOOL_STEPS', 4),
    ],
];
