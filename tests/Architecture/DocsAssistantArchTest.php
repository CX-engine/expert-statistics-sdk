<?php

// Enforces, at the architecture level rather than just by convention, the
// hard constraints the docs assistant was explicitly built with.
//
// DocsAssistantChat now legitimately calls ExpertStatisticsService (via
// confirmAction(), only reachable from an explicit human Confirm click, only
// after a successful validate call - see the ordering tests in
// DocsAssistantChatTest.php) since it grew a second, action-capable mode.
// The one component that must NEVER be able to see call data, full stop, is
// PrismDocsAssistantResponder - the plain doc-Q&A responder used whenever
// ai_actions.enabled is false, or for any turn that mode doesn't handle.

$noPersistenceClasses = [
    'CXEngine\ExpertStatistics\Livewire\Docs\DocsAssistantChat',
    'CXEngine\ExpertStatistics\Services\PrismDocsAssistantResponder',
    'CXEngine\ExpertStatistics\Services\PrismActionAssistantResponder',
];

arch('the docs/action assistants never touch persistence directly (database, cache, or session)')
    ->expect($noPersistenceClasses)
    ->not->toUse([
        'Illuminate\Support\Facades\DB',
        'Illuminate\Database\Eloquent\Model',
        'Illuminate\Support\Facades\Cache',
        'Illuminate\Support\Facades\Session',
    ]);

arch('the read-only docs responder never references ExpertStatisticsService (no call-data/stats access)')
    ->expect('CXEngine\ExpertStatistics\Services\PrismDocsAssistantResponder')
    ->not->toUse('CXEngine\ExpertStatistics\Services\ExpertStatisticsService');
