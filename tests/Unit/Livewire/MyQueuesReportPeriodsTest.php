<?php

use CXEngine\ExpertStatistics\Contracts\ChecksExpertStatisticsActivation;
use CXEngine\ExpertStatistics\Livewire\Reports\MyQueues\MyQueuesReport;
use CXEngine\ExpertStatistics\Tests\TestCase;
use Illuminate\Foundation\Auth\User as GenericUser;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

uses(TestCase::class);

function queuePeriodRow(string $period, string $start, string $end, bool $partial, int $calls, int $answered): array
{
    return [
        'day' => $start, 'queue' => '800', 'queueName' => 'Support', 'queueNameNumber' => '800-Support',
        'element' => '800', 'elementName' => 'Support', 'elementNameNumber' => '800-Support', 'keyType' => '4',
        'answered' => $answered, 'talking_duration' => $answered * 120, 'waiting_duration' => $answered * 20,
        'preanswer_duration' => 0, 'calls' => $calls, 'internal_calls' => 0, 'internal_answered' => 0,
        'dissuaded' => 0, 'transferred' => 0, 'abandoned' => $calls - $answered, 'preanswer_abandoned' => 0,
        'solicited' => $calls,
        'period' => $period, 'period_label' => $period, 'period_start' => $start, 'period_end' => $end,
        'period_days' => 1, 'partial' => $partial,
    ];
}

function queuePeriodMetric(array $o = []): array
{
    return array_merge([
        'current' => 0, 'usual' => 0, 'delta' => 0, 'delta_percent' => 0.0, 'scaled' => false,
        'trend' => 'flat', 'assessment' => 'neutral', 'severity' => null, 'score' => 0.0,
    ], $o);
}

/** The API's anomaly detection: February is worse than the queue's usual level (January). */
function queuePeriodsResponse(): array
{
    $identity = ['queue' => '800', 'queueName' => 'Support', 'element' => '800', 'elementName' => 'Support', 'keyType' => '4'];
    $rate = queuePeriodMetric(['current' => 70.0, 'usual' => 90.0, 'delta' => -20.0, 'delta_percent' => -22.2, 'trend' => 'down', 'assessment' => 'worse', 'severity' => 'major', 'score' => 2.0]);
    $calls = queuePeriodMetric(['current' => 200, 'usual' => 164.7, 'delta' => 35.3, 'delta_percent' => 21.4, 'scaled' => true, 'trend' => 'up', 'severity' => 'notable']);

    return [
        'report' => [
            queuePeriodRow('2026-01', '2026-01-15', '2026-01-31', true, 100, 90),
            queuePeriodRow('2026-02', '2026-02-01', '2026-02-28', false, 200, 140),
        ],
        'aggregated' => ['inbound_calls' => 300, 'answered_calls' => 230],
        'available_periods' => ['total', 'day', 'week', 'month'],
        'period' => 'month',
        'periods' => [
            ['key' => '2026-01', 'label' => '2026-01', 'start' => '2026-01-15', 'end' => '2026-01-31', 'days' => 17, 'partial' => true],
            ['key' => '2026-02', 'label' => '2026-02', 'start' => '2026-02-01', 'end' => '2026-02-28', 'days' => 28, 'partial' => false],
        ],
        'comparison' => [array_merge($identity, [
            'period' => '2026-02', 'basis' => 'range', 'reference_periods' => ['2026-01'], 'severity' => 'major',
            'metrics' => [
                'answer_rate' => $rate,
                'avg_waiting' => queuePeriodMetric(),
                'abandoned' => queuePeriodMetric(),
                'calls' => $calls,
            ],
        ])],
        'anomalies' => [array_merge($identity, [
            'period' => '2026-02', 'basis' => 'range', 'severity' => 'major', 'priority' => 4.6,
            'issues' => ['answer_rate' => $rate],
            'context' => ['calls' => $calls],
        ])],
    ];
}

beforeEach(function () {
    $this->useHostComponentDoubles();
    Blade::anonymousComponentPath(__DIR__.'/../../Doubles/views/filament', 'filament');
    Cache::flush();
    app()->setLocale('en');

    $this->mockClient = MockClient::global([
        '*queues-report-aggregated*' => MockResponse::make(queuePeriodsResponse(), 200),
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

function sentQueueReportQueries(MockClient $client): array
{
    return collect($client->getRecordedResponses())
        ->map(fn ($r) => $r->getPendingRequest())
        ->filter(fn ($p) => str_contains($p->getUrl(), 'queues-report-aggregated'))
        ->map(fn ($p) => $p->query()->all())
        ->values()
        ->all();
}

it('loads the month view and points out the periods worse than usual', function () {
    Livewire::test(MyQueuesReport::class)
        ->set('startDate', '2026-01-15')
        ->set('endDate', '2026-02-28')
        ->set('dn', '800')
        ->call('selectBreakdown', 'month')
        ->assertSet('breakdown', 'month')
        ->assertSet('groupData', false)
        ->assertSet('compare', false)
        ->assertDontSee('Points of attention')
        ->set('compare', true)
        ->assertSee('January 2026')
        ->assertSee('Partial period: 01/15 – 01/31')
        ->assertSee('Points of attention')
        ->assertSee('Each period is compared with the usual level of its queue, over the other selected periods.')
        ->assertSee('800 Support')
        ->assertSee('February 2026')
        ->assertSee('Answer rate 70.0 % (usually 90.0 %)')
        ->assertSee('21 % more calls than usual')
        // The table marks the abnormal answer rate only, not the calls (not judged).
        ->assertSee('▼ usually 90.0 %')
        ->assertSee('Answer rate worse than usual (usually 90.0 %)')
        ->assertDontSee('usually 165');

    $last = collect(sentQueueReportQueries($this->mockClient))->last();
    expect($last)->toMatchArray(['period' => 'month', 'compare' => 1, 'grouped' => 0, 'dn' => '800']);
});

it('says when no period is abnormal', function () {
    $response = queuePeriodsResponse();
    $response['anomalies'] = [];
    $response['comparison'][0]['metrics']['answer_rate'] = queuePeriodMetric(['current' => 70.0, 'usual' => 71.0]);
    MockClient::destroyGlobal();
    $this->mockClient = MockClient::global([
        '*queues-report-aggregated*' => MockResponse::make($response, 200),
        '*' => MockResponse::make([], 200),
    ]);

    Livewire::test(MyQueuesReport::class)
        ->set('startDate', '2026-01-15')
        ->set('endDate', '2026-02-28')
        ->set('dn', '800')
        ->call('selectBreakdown', 'month')
        ->set('compare', true)
        ->assertSee('No anomaly: every period is within the usual range.')
        ->assertDontSee('▼ usually');
});

it('keeps the comparison off by default and as the user chose it across views', function () {
    $component = Livewire::test(MyQueuesReport::class)
        ->set('startDate', '2026-01-15')
        ->set('endDate', '2026-02-28')
        ->set('dn', '800')
        ->call('selectBreakdown', 'week')
        ->assertSet('compare', false);

    expect(collect(sentQueueReportQueries($this->mockClient))->last())->toMatchArray(['period' => 'week', 'compare' => 0]);

    $component->set('compare', true)
        ->call('selectBreakdown', 'month')
        ->assertSet('compare', true)
        ->assertSee('Points of attention');

    expect(collect(sentQueueReportQueries($this->mockClient))->last())->toMatchArray(['period' => 'month', 'compare' => 1]);

    $component->set('compare', false)
        ->assertDontSee('Points of attention');
});

it('shows average talk and wait instead of totals', function () {
    // Fixture rows: answered 90 / 140, talk 120 s and wait 20 s per answered call, no message.
    Livewire::test(MyQueuesReport::class)
        ->set('startDate', '2026-01-15')
        ->set('endDate', '2026-02-28')
        ->set('dn', '800')
        ->call('selectBreakdown', 'month')
        ->assertSee(__('expert-statistics::pbx.queue_report_periods.col_avg_talk'))
        ->assertSee(__('expert-statistics::pbx.queue_report_periods.col_avg_wait'))
        ->assertSee('2m 0s')
        ->assertSee('20s')
        ->assertDontSee('3h 0m'); // 90 × 120 s: the old total
});

it('falls back to the total view when the range no longer allows the selection', function () {
    Livewire::test(MyQueuesReport::class)
        ->set('startDate', '2026-01-15')
        ->set('endDate', '2026-02-28')
        ->set('dn', '800')
        ->call('selectBreakdown', 'month')
        ->assertSet('breakdown', 'month')
        ->call('setCustomRange', '2026-02-02', '2026-02-05')
        ->assertSet('breakdown', 'total')
        ->assertSet('groupData', true)
        ->assertSet('availableBreakdowns', ['total', 'day', 'week', 'month']) // from the (mocked) API answer
        ->call('selectBreakdown', 'nonsense')
        ->assertSet('breakdown', 'total');

    expect(collect(sentQueueReportQueries($this->mockClient))->last())->toMatchArray(['period' => 'total'])
        ->not->toHaveKey('compare');
});

it('refuses a view the range does not allow before the first load', function () {
    MockClient::destroyGlobal();
    $this->mockClient = MockClient::global(['*' => MockResponse::make([], 200)]);

    Livewire::test(MyQueuesReport::class)
        ->call('setCustomRange', '2026-02-02', '2026-02-05')
        ->assertSet('availableBreakdowns', ['total', 'day'])
        ->call('selectBreakdown', 'month')
        ->assertSet('breakdown', 'total');
});

it('consolidating forces the total view', function () {
    Livewire::test(MyQueuesReport::class)
        ->set('startDate', '2026-01-15')
        ->set('endDate', '2026-02-28')
        ->set('dn', '800')
        ->call('selectBreakdown', 'month')
        ->set('consolidateData', true)
        ->assertSet('breakdown', 'total')
        ->assertSet('groupData', true);
});

it('passes the period and compare to the export url', function () {
    Route::get('/expert-statistics/my-queues/export', fn () => '')->name('expert-stats.my-queues.export');
    Route::getRoutes()->refreshNameLookups();

    $component = Livewire::test(MyQueuesReport::class)
        ->set('startDate', '2026-01-15')
        ->set('endDate', '2026-02-28')
        ->set('dn', '800');

    parse_str((string) parse_url($component->instance()->getExportUrl(), PHP_URL_QUERY), $total);
    expect($total)->not->toHaveKey('period')->not->toHaveKey('compare');

    $component->call('selectBreakdown', 'month')->set('compare', false);
    parse_str((string) parse_url($component->instance()->getExportUrl(), PHP_URL_QUERY), $month);

    expect($month)->toMatchArray(['period' => 'month', 'compare' => '0', 'dn' => '800', 'start_date' => '2026-01-15']);
});

it('forwards period and compare=0 to the export file request, and drops them for the total view', function (array $query, array $expected, array $absent) {
    Route::get('/expert-statistics/my-queues/export', \CXEngine\ExpertStatistics\Http\Controllers\QueuesReportExportController::class)->name('expert-stats.my-queues.export');
    Route::getRoutes()->refreshNameLookups();
    MockClient::destroyGlobal();
    $this->mockClient = MockClient::global(['*' => MockResponse::make('xlsx-bytes', 200)]);

    $this->get('/expert-statistics/my-queues/export?'.http_build_query(['start_date' => '2026-01-15', 'end_date' => '2026-02-28', 'dn' => '800', ...$query]))
        ->assertOk();

    $body = $this->mockClient->getLastPendingRequest()->body()?->all() ?? [];
    expect($body)->toMatchArray($expected);
    foreach ($absent as $key) {
        expect($body)->not->toHaveKey($key);
    }
})->with([
    'month without comparison' => [['period' => 'month', 'compare' => '0'], ['period' => 'month', 'compare' => '0', 'dn' => '800'], []],
    'total' => [['period' => 'total', 'compare' => '1'], ['dn' => '800'], ['period', 'compare']],
]);

it('renders the extensions view and the day view with localised dates', function () {
    app()->setLocale('fr');

    Livewire::test(MyQueuesReport::class)
        ->set('startDate', '2026-01-15')
        ->set('endDate', '2026-02-28')
        ->set('dn', '800')
        ->call('selectBreakdown', 'month')
        ->set('queuesOnlyStats', false)
        ->assertSee('Février 2026')
        ->assertSee('Déclinés')
        ->call('selectBreakdown', 'day')
        ->assertSee('15/01/2026')
        ->assertDontSee('2026-01-15</td>', false);
});

it('selects the last 3 and 6 whole months, and last month without overflowing', function () {
    \Carbon\Carbon::setTestNow('2026-11-18 10:00:00');

    $component = Livewire::test(MyQueuesReport::class)
        ->call('selectPeriod', 'last3Months')
        ->assertSet('startDate', '2026-08-01')
        ->assertSet('endDate', '2026-10-31')
        ->assertSee('Last 3 months')
        ->call('selectPeriod', 'last6Months')
        ->assertSet('startDate', '2026-05-01')
        ->assertSet('endDate', '2026-10-31');

    expect($component->get('availableBreakdowns'))->toContain('month');

    // March 31 minus one month must be February, not March 3.
    \Carbon\Carbon::setTestNow('2026-03-31 10:00:00');
    Livewire::test(MyQueuesReport::class)
        ->call('selectPeriod', 'lastMonth')
        ->assertSet('startDate', '2026-02-01')
        ->assertSet('endDate', '2026-02-28');

    \Carbon\Carbon::setTestNow();
});
