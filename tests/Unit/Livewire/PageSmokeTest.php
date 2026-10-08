<?php

use CXEngine\ExpertStatistics\Contracts\ChecksExpertStatisticsActivation;
use CXEngine\ExpertStatistics\Livewire\AgentMonitoring\QueueConnection;
use CXEngine\ExpertStatistics\Livewire\AgentMonitoring\RealtimeStatus;
use CXEngine\ExpertStatistics\Livewire\AgentMonitoring\StatusBreakdown;
use CXEngine\ExpertStatistics\Livewire\Ai\AiAlerts;
use CXEngine\ExpertStatistics\Livewire\Ai\AiChat;
use CXEngine\ExpertStatistics\Livewire\Ai\AiDashboard;
use CXEngine\ExpertStatistics\Livewire\Ai\AiFloatingChat;
use CXEngine\ExpertStatistics\Livewire\CallAnalysis\CallAnalysis;
use CXEngine\ExpertStatistics\Livewire\Configuration\ManagePbxSettings;
use CXEngine\ExpertStatistics\Livewire\Dashboard;
use CXEngine\ExpertStatistics\Livewire\Home;
use CXEngine\ExpertStatistics\Livewire\Reports\CallerNumbers\CallerNumbersReport;
use CXEngine\ExpertStatistics\Livewire\Reports\ManageScheduledReports;
use CXEngine\ExpertStatistics\Livewire\Reports\MyNumbers\MyNumbersReport;
use CXEngine\ExpertStatistics\Livewire\Reports\MyQueues\MyQueuesDashboard;
use CXEngine\ExpertStatistics\Livewire\Reports\MyQueues\MyQueuesKpi;
use CXEngine\ExpertStatistics\Livewire\Reports\MyQueues\MyQueuesOrigins;
use CXEngine\ExpertStatistics\Livewire\Reports\MyQueues\MyQueuesReport;
use CXEngine\ExpertStatistics\Livewire\Reports\MyUsers\MyUsersDashboard;
use CXEngine\ExpertStatistics\Livewire\Reports\MyUsers\MyUsersKpi;
use CXEngine\ExpertStatistics\Livewire\Reports\MyUsers\MyUsersOrigins;
use CXEngine\ExpertStatistics\Livewire\Reports\MyUsers\MyUsersOutbound;
use CXEngine\ExpertStatistics\Livewire\Reports\MyUsers\MyUsersReport;
use CXEngine\ExpertStatistics\Livewire\Reports\ShareReportModal;
use CXEngine\ExpertStatistics\Tests\TestCase;
use Illuminate\Foundation\Auth\User as GenericUser;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

uses(TestCase::class);

/*
 * Mounts and renders every component against an XP-Stats API that answers
 * every call with an empty payload - catching framework-level breakage
 * (Livewire 3 vs 4, Blade compilation, missing view variables) on whichever
 * Laravel / Livewire / Filament line the suite is installed against.
 */

beforeEach(function () {
    // Both host apps run Filament; the Dashboard uses its loading indicator.
    Blade::anonymousComponentPath(__DIR__.'/../../Doubles/views/filament', 'filament');

    MockClient::global(['*' => MockResponse::make([], 200)]);

    $this->app->instance(ChecksExpertStatisticsActivation::class, new class implements ChecksExpertStatisticsActivation
    {
        public function isActive(): bool
        {
            return true;
        }
    });

    Gate::before(fn () => true);
    $user = new GenericUser;
    $user->id = 1;
    $user->name = 'Jane';
    $user->email = 'jane@example.com';
    $this->actingAs($user);
});

afterEach(function () {
    MockClient::destroyGlobal();
});

$components = [
    'home' => Home::class,
    'dashboard' => Dashboard::class,
    'my queues report' => MyQueuesReport::class,
    'my queues dashboard' => MyQueuesDashboard::class,
    'my queues kpi' => MyQueuesKpi::class,
    'my queues origins' => MyQueuesOrigins::class,
    'my users report' => MyUsersReport::class,
    'my users dashboard' => MyUsersDashboard::class,
    'my users kpi' => MyUsersKpi::class,
    'my users origins' => MyUsersOrigins::class,
    'my users outbound' => MyUsersOutbound::class,
    'my numbers' => MyNumbersReport::class,
    'caller numbers' => CallerNumbersReport::class,
    'call analysis' => CallAnalysis::class,
    'realtime status' => RealtimeStatus::class,
    'queue connection' => QueueConnection::class,
    'status breakdown' => StatusBreakdown::class,
    'ai chat' => AiChat::class,
    'ai dashboard' => AiDashboard::class,
    'ai alerts' => AiAlerts::class,
    'ai floating chat' => AiFloatingChat::class,
    'pbx settings' => ManagePbxSettings::class,
    'scheduled reports' => ManageScheduledReports::class,
    'share report modal' => ShareReportModal::class,
];

/*
 * The component's root element must be the very first thing it renders:
 * Livewire attaches wire:id / wire:snapshot to the first tag that starts
 * the output or a new line, so anything ahead of it - e.g. the
 * <!--[if BLOCK]> marker of an @if wrapping the root - lands them on an
 * inner element and every later update fails with "Snapshot missing".
 */
function assertRendersRootFirst(string $component): void
{
    $html = Livewire::test($component)->assertOk()->html();

    expect(ltrim($html))->toMatch('/^<[a-z][^>]*\swire:snapshot=/');
}

it('renders inside the host page shell, root element first', function (string $component) {
    $this->useHostComponentDoubles();

    assertRendersRootFirst($component);
})->with($components);

it('renders without the host page shell, root element first', function (string $component) {
    config()->set('expert-statistics-api.page_shell', false);

    assertRendersRootFirst($component);
})->with($components);

it('gives its nested components fixed keys, so an update never remounts them', function () {
    // Livewire 3's generated keys include a loop counter that the nested
    // components' own @foreach loops move on the first render but not on
    // updates (when they are skipped) - a generated key would change and
    // remount them on every update.
    $this->useHostComponentDoubles();
    config()->set('expert-statistics-api.docs_assistant.enabled', true);

    $children = Livewire::test(MyQueuesReport::class)->snapshot['memo']['children'];

    expect(array_keys($children))->toEqualCanonicalizing([
        'expert-statistics.reports.share-report-modal',
        'expert-statistics.docs.helper-panel',
        'expert-statistics.docs.assistant-chat',
    ]);
});
