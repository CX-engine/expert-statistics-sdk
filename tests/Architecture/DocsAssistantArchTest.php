<?php

// Enforces, at the architecture level rather than just by convention, the
// two hard constraints the docs assistant was explicitly built with: no
// persistence anywhere, and no access to call-data/statistics. If either of
// these is ever violated - even unintentionally, e.g. someone "helpfully"
// injects ExpertStatisticsService to make an answer richer - this test
// fails immediately instead of the constraint silently rotting away.

$docsAssistantClasses = [
    'CXEngine\ExpertStatistics\Livewire\Docs\DocsAssistantChat',
    'CXEngine\ExpertStatistics\Services\PrismDocsAssistantResponder',
];

arch('the docs assistant never touches persistence (database, cache, or session)')
    ->expect($docsAssistantClasses)
    ->not->toUse([
        'Illuminate\Support\Facades\DB',
        'Illuminate\Database\Eloquent\Model',
        'Illuminate\Support\Facades\Cache',
        'Illuminate\Support\Facades\Session',
    ]);

arch('the docs assistant never references ExpertStatisticsService (no call-data/stats access)')
    ->expect($docsAssistantClasses)
    ->not->toUse('CXEngine\ExpertStatistics\Services\ExpertStatisticsService');
