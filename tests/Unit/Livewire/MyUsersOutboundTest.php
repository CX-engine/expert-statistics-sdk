<?php

use CXEngine\ExpertStatistics\Contracts\ChecksExpertStatisticsActivation;
use CXEngine\ExpertStatistics\Livewire\Reports\MyUsers\MyUsersDashboard;
use CXEngine\ExpertStatistics\Livewire\Reports\MyUsers\MyUsersOutbound;
use CXEngine\ExpertStatistics\Livewire\Reports\ShareReportModal;
use CXEngine\ExpertStatistics\Tests\TestCase;
use Illuminate\Foundation\Auth\User as GenericUser;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

uses(TestCase::class);

function outboundRow(string $user, int $calls, int $answered): array
{
    return [
        'user' => $user,
        'user_dn' => explode('-', $user)[0],
        'outbound_calls' => $calls,
        'outbound_answered' => $answered,
        'outbound_unanswered' => $calls - $answered,
        'outbound_answered_percentage' => $calls > 0 ? round($answered / $calls * 100) : 0,
        'outbound_talking_duration_total' => $answered * 60,
        'outbound_talking_duration_avg' => $answered > 0 ? 60 : 0,
        'outbound_ringing_duration_avg' => 8,
        'outbound_unique_numbers' => $calls,
    ];
}

beforeEach(function () {
    $this->useHostComponentDoubles();
    Blade::anonymousComponentPath(__DIR__.'/../../Doubles/views/filament', 'filament');

    $this->mockClient = MockClient::global([
        '*users-outbound-report*' => MockResponse::make([
            'report' => [outboundRow('101-Alice', 2, 1), outboundRow('102-Bob', 5, 4)],
            'aggregated' => outboundRow('total', 6, 5),
        ], 200),
        '*users-outbound-calls*' => MockResponse::make([
            ['hour' => '2026-09-01 09:00:00', 'outbound_calls' => 4, 'answered_outbound_calls' => 3, 'total_talking_duration_in_seconds' => 180],
            ['hour' => '2026-09-02 10:00:00', 'outbound_calls' => 2, 'answered_outbound_calls' => 2, 'total_talking_duration_in_seconds' => 120],
        ], 200),
        '*' => MockResponse::make([], 200),
    ]);

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

it('shows each selected user, the totals and that only external calls are counted', function () {
    Livewire::test(MyUsersOutbound::class)
        ->set('dn', '101,102')
        ->call('loadData')
        ->assertSet('aggregated.outbound_calls', 6)
        ->assertSee('101-Alice')
        ->assertSee('102-Bob')
        ->assertSee(__('expert-statistics::pbx.expert_statistics.my_users_outbound_external_only'));

    $this->mockClient->assertSent(fn ($request, $response) => str_contains($response->getPendingRequest()->getUrl(), '/test-host.on3cx.fr/users-outbound-report')
        && $response->getPendingRequest()->query()->get('dn') === '101,102');
});

it('sends and schedules the report as a users outbound report', function (string $action, string $event) {
    Livewire::test(MyUsersOutbound::class)
        ->set('dn', '101,102')
        ->call($action)
        ->assertDispatched($event, fn (string $name, array $params): bool => $params['reportType'] === 'userOutboundReport' && $params['elements'] === '101,102');
})->with([
    'send' => ['shareReport', 'open-share-report'],
    'schedule' => ['scheduleReport', 'open-schedule-report'],
]);

it('stores a scheduled users report with its users and element type', function (?string $reportType, string $expectedType) {
    Livewire::test(ShareReportModal::class)
        ->call('openSchedule', urlType: 'extension', startDate: '2026-09-01', endDate: '2026-09-30', elements: '101,102', reportType: $reportType)
        ->call('submit');

    // element_type '*' would make the API drop the users (dns) when it builds the report.
    $this->mockClient->assertSent(function ($request, $response) use ($expectedType): bool {
        $body = $response->getPendingRequest()->body()?->all() ?? [];

        return ($body['report_type'] ?? null) === $expectedType
            && ($body['element_type'] ?? null) === 0
            && ($body['dns'] ?? null) === '101,102'
            && ($body['repeat'] ?? null) === true;
    });
})->with([
    'users report' => [null, 'userReport'],
    'users outbound report' => ['userOutboundReport', 'userOutboundReport'],
]);

it('charts the selected users\' outbound calls by hour, by day and per user', function () {
    $component = Livewire::test(MyUsersDashboard::class)
        ->set('startDate', '2026-09-01')
        ->set('endDate', '2026-09-02')
        ->set('selectedElements', ['101', '102'])
        ->call('loadData')
        ->assertSet('outboundKpis.totalCalls', 6)
        ->assertSet('outboundKpis.totalAnswered', 5)
        ->assertSet('outboundByUser.categories', ['102-Bob', '101-Alice'])
        ->assertSee(__('expert-statistics::pbx.dashboards.outboundCallsPerUser'));

    $byDay = $component->call('setOutboundChartView', 'day')->instance()->getOutboundRows();

    expect($byDay)->toHaveCount(2)
        ->and($byDay[0])->toMatchArray(['period' => '2026-09-01', 'answered' => 3, 'unanswered' => 1]);
});
