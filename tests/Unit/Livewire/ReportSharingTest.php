<?php

use CXEngine\ExpertStatistics\Contracts\ChecksExpertStatisticsActivation;
use CXEngine\ExpertStatistics\Livewire\CallAnalysis\CallAnalysis;
use CXEngine\ExpertStatistics\Livewire\Reports\MyQueues\MyQueuesDashboard;
use CXEngine\ExpertStatistics\Livewire\Reports\MyQueues\MyQueuesKpi;
use CXEngine\ExpertStatistics\Livewire\Reports\MyQueues\MyQueuesOrigins;
use CXEngine\ExpertStatistics\Livewire\Reports\MyUsers\MyUsersDashboard;
use CXEngine\ExpertStatistics\Livewire\Reports\MyUsers\MyUsersKpi;
use CXEngine\ExpertStatistics\Livewire\Reports\MyUsers\MyUsersOrigins;
use CXEngine\ExpertStatistics\Livewire\Reports\ShareReportModal;
use CXEngine\ExpertStatistics\Tests\TestCase;
use Illuminate\Foundation\Auth\User as GenericUser;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

uses(TestCase::class);

beforeEach(function () {
    $this->useHostComponentDoubles();
    Blade::anonymousComponentPath(__DIR__.'/../../Doubles/views/filament', 'filament');

    $this->mockClient = MockClient::global(['*' => MockResponse::make([], 200)]);

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
    $user->email = 'jane@example.com';
    $this->actingAs($user);
});

afterEach(function () {
    MockClient::destroyGlobal();
});

/** The body of the report the modal stored through the API. */
function storedReport(MockClient $mockClient): array
{
    $body = [];

    $mockClient->assertSent(function ($request, $response) use (&$body): bool {
        $body = $response->getPendingRequest()->body()?->all() ?? [];

        return isset($body['report_type']);
    });

    return $body;
}

it('offers send and schedule with the page report type', function (string $component, string $urlType, string $reportType) {
    Livewire::test($component)
        ->assertSeeHtml('wire:click="shareReport"')
        ->assertSeeHtml('wire:click="scheduleReport"')
        ->set('selectedElements', ['800', '801'])
        ->call('shareReport')
        ->assertDispatched('open-share-report', fn (string $name, array $params): bool => $params['reportType'] === $reportType
            && $params['urlType'] === $urlType
            && $params['elements'] === '800,801')
        ->call('scheduleReport')
        ->assertDispatched('open-schedule-report', fn (string $name, array $params): bool => $params['reportType'] === $reportType);
})->with([
    'my queues dashboard' => [MyQueuesDashboard::class, 'queue', 'dashboard'],
    'my queues kpi' => [MyQueuesKpi::class, 'queue', 'answered'],
    'my queues origins' => [MyQueuesOrigins::class, 'queue', 'origins'],
    'my users dashboard' => [MyUsersDashboard::class, 'extension', 'dashboard'],
    'my users kpi' => [MyUsersKpi::class, 'extension', 'answered'],
    'my users origins' => [MyUsersOrigins::class, 'extension', 'origins'],
]);

it('stores the report with its elements and their element type, which the API uses to pick queue or extension data', function (string $urlType, string $reportType, int $elementType) {
    Livewire::test(ShareReportModal::class)
        ->call('openSchedule', urlType: $urlType, startDate: '2026-09-01', endDate: '2026-09-30', elements: '800,801', reportType: $reportType)
        ->call('submit');

    expect(storedReport($this->mockClient))->toMatchArray([
        'report_type' => $reportType,
        'element_type' => $elementType,
        'dns' => '800,801',
        'repeat' => true,
    ]);
})->with([
    'queues dashboard' => ['queue', 'dashboard', 4],
    'queues kpi' => ['queue', 'answered', 4],
    'queues origins' => ['queue', 'origins', 4],
    'users dashboard' => ['extension', 'dashboard', 0],
    'users kpi' => ['extension', 'answered', 0],
    'users origins' => ['extension', 'origins', 0],
]);

it('sends the call analysis calls with their filters', function () {
    Livewire::test(CallAnalysis::class)
        ->assertSeeHtml('wire:click="shareReport"')
        ->set('startDate', '2026-09-01')
        ->set('endDate', '2026-09-30')
        ->set('callWay', 'outbound')
        ->set('originDn', '101')
        ->set('callStatus', 'unanswered')
        ->call('shareReport')
        ->assertDispatched('open-share-report', fn (string $name, array $params): bool => $params['reportType'] === 'cdrReport'
            && $params['filters'] === ['origin_dn' => '101', 'call_way' => 'outbound', 'call_status' => 'unanswered']);

    Livewire::test(ShareReportModal::class)
        ->call('openShare', urlType: 'cdr', startDate: '2026-09-01', endDate: '2026-09-30', reportType: 'cdrReport', filters: ['call_way' => 'outbound', 'origin_dn' => '101'])
        ->assertSet('resourceGroups', [])
        ->call('submit');

    expect(storedReport($this->mockClient))->toMatchArray([
        'report_type' => 'cdrReport',
        'element_type' => '*',
        'dns' => '*',
        'filters' => ['call_way' => 'outbound', 'origin_dn' => '101'],
        'repeat' => false,
    ]);
});
