<?php

use CXEngine\ExpertStatistics\Support\PbxDataProcessor;

it('groups inbound records by day and by hour', function () {
    $records = [
        ['hour' => '2026-01-01 08:00:00', 'inbound_calls' => 5, 'answered_inbound_calls' => 4, 'abandoned_within_preanswer' => 1, 'total_talking_duration_in_seconds' => 100, 'total_waiting_duration_in_seconds' => 20, 'ten_seconds_calls' => 1],
        ['hour' => '2026-01-01 09:00:00', 'inbound_calls' => 3, 'answered_inbound_calls' => 3, 'abandoned_within_preanswer' => 0, 'total_talking_duration_in_seconds' => 60, 'total_waiting_duration_in_seconds' => 10, 'ten_seconds_calls' => 0],
        ['hour' => '2026-01-02 08:00:00', 'inbound_calls' => 2, 'answered_inbound_calls' => 2, 'abandoned_within_preanswer' => 0, 'total_talking_duration_in_seconds' => 40, 'total_waiting_duration_in_seconds' => 5, 'ten_seconds_calls' => 0],
    ];

    $grouped = PbxDataProcessor::groupInboundByDayAndHour($records);

    expect($grouped['byDay'])->toHaveCount(2)
        ->and(collect($grouped['byDay'])->firstWhere('date', '2026-01-01')['inbound_calls'])->toBe(8)
        ->and(collect($grouped['byDay'])->firstWhere('date', '2026-01-02')['inbound_calls'])->toBe(2)
        ->and($grouped['byHour'])->toHaveCount(2)
        ->and(collect($grouped['byHour'])->firstWhere('hour', '08:00')['inbound_calls'])->toBe(7);
});

it('calculates inbound KPIs from grouped hourly data', function () {
    $grouped = [
        'byHour' => [
            ['hour' => '08:00', 'inbound_calls' => 10, 'answered_inbound_calls' => 8, 'abandoned_within_preanswer' => 1, 'total_waiting_duration_in_seconds' => 80, 'total_talking_duration_in_seconds' => 500, 'ten_seconds_calls' => 2],
            ['hour' => '09:00', 'inbound_calls' => 5, 'answered_inbound_calls' => 5, 'abandoned_within_preanswer' => 0, 'total_waiting_duration_in_seconds' => 20, 'total_talking_duration_in_seconds' => 300, 'ten_seconds_calls' => 0],
        ],
    ];

    $kpis = PbxDataProcessor::calcInboundKpis($grouped);

    expect($kpis['totalCalls'])->toBe(15)
        ->and($kpis['totalAnswered'])->toBe(13)
        ->and($kpis['lostCalls'])->toBe(1)
        ->and($kpis['avgWaitSeconds'])->toBe(round(100 / 13, 1))
        ->and($kpis['mostCallsHour'])->toBe('08:00')
        ->and($kpis['bestAnsweredRate'])->toBe(100.0);
});

it('calculates outbound KPIs from grouped hourly data', function () {
    $grouped = [
        'byHour' => [
            ['hour' => '08:00', 'outbound_calls' => 4, 'answered_outbound_calls' => 2, 'total_talking_duration_in_seconds' => 100],
            ['hour' => '09:00', 'outbound_calls' => 6, 'answered_outbound_calls' => 6, 'total_talking_duration_in_seconds' => 200],
        ],
    ];

    $kpis = PbxDataProcessor::calcOutboundKpis($grouped);

    expect($kpis['totalCalls'])->toBe(10)
        ->and($kpis['totalAnswered'])->toBe(8)
        ->and($kpis['lostCalls'])->toBe(2)
        ->and($kpis['bestAnsweredRate'])->toBe(100.0)
        ->and($kpis['mostCallsHour'])->toBe('09:00');
});

it('builds a gap-filled answered/unanswered chart by day', function () {
    $byDay = [
        ['date' => '2026-01-01', 'inbound_calls' => 10, 'answered_inbound_calls' => 7, 'abandoned_within_preanswer' => 1],
    ];

    $chart = PbxDataProcessor::buildAnsweredChartByDay($byDay, '2026-01-01', '2026-01-03');

    expect($chart['categories'])->toBe(['2026-01-01', '2026-01-02', '2026-01-03'])
        ->and($chart['series'][0]['data'])->toBe([7, 0, 0])
        ->and($chart['series'][1]['data'])->toBe([2, 0, 0]);
});

it('builds the top-10 users chart sorted by total calls', function () {
    $users = [
        ['display_name' => 'Alice', 'total_calls' => 5, 'inbound_calls' => 2, 'outbound_calls' => 2, 'internal_calls_made' => 1, 'internal_calls_received' => 0],
        ['display_name' => 'Bob', 'total_calls' => 20, 'inbound_calls' => 10, 'outbound_calls' => 8, 'internal_calls_made' => 1, 'internal_calls_received' => 1],
    ];

    $chart = PbxDataProcessor::buildTopUsersChart($users);

    expect($chart['labels'])->toBe(['Bob', 'Alice'])
        ->and($chart['series'][0]['data'])->toBe([10, 2])
        ->and($chart['series'][2]['data'])->toBe([2, 1]);
});

it('parses unique-calls-period totals per call direction', function () {
    $data = [
        ['call_way' => 'inbound', 'unique_calls' => 3, 'total_duration_seconds' => 90],
        ['call_way' => 'inbound', 'unique_calls' => 2, 'total_duration_seconds' => 30],
        ['call_way' => 'outbound', 'unique_calls' => 5, 'total_duration_seconds' => 200],
        ['call_way' => 'unknown', 'unique_calls' => 99, 'total_duration_seconds' => 999],
    ];

    $totals = PbxDataProcessor::parsePbxTotals($data);

    expect($totals['inbound'])->toBe(['calls' => 5, 'duration' => 120])
        ->and($totals['outbound'])->toBe(['calls' => 5, 'duration' => 200])
        ->and($totals['internal'])->toBe(['calls' => 0, 'duration' => 0]);
});

it('calculates current vs previous month trend KPIs', function () {
    $currentMonth = now()->format('Y-m-01');
    $previousMonth = now()->subMonth()->format('Y-m-01');

    $trendData = [
        ['month' => $previousMonth, 'inbound_calls' => 100, 'answered_inbound_calls' => 50, 'avg_waiting_duration_in_seconds' => 20],
        ['month' => $currentMonth, 'inbound_calls' => 150, 'answered_inbound_calls' => 120, 'avg_waiting_duration_in_seconds' => 10],
    ];

    $trends = PbxDataProcessor::calcTrends($trendData);

    expect($trends['totalCalls'])->toBe(150)
        ->and($trends['prevTotalCalls'])->toBe(100)
        ->and($trends['carCalls'])->toBe(80.0)
        ->and($trends['totalTrend'])->toBe(50.0)
        ->and($trends['waitTimeTrend'])->toBe(-50.0);
});

it('formats seconds to hour/minute and minute/second strings', function () {
    expect(PbxDataProcessor::formatSecsToHourMin(0))->toBe('0min')
        ->and(PbxDataProcessor::formatSecsToHourMin(90))->toBe('1min')
        ->and(PbxDataProcessor::formatSecsToHourMin(3660))->toBe('1h 1min')
        ->and(PbxDataProcessor::formatSecsToHourMin(7200))->toBe('2h')
        ->and(PbxDataProcessor::formatSecsToMinSec(0))->toBe('0:00')
        ->and(PbxDataProcessor::formatSecsToMinSec(65))->toBe('1:05');
});

it('generates an inclusive date range', function () {
    $dates = PbxDataProcessor::generateDateRange('2026-01-01', '2026-01-03');

    expect($dates)->toHaveCount(3)
        ->and($dates[0]->format('Y-m-d'))->toBe('2026-01-01')
        ->and($dates[2]->format('Y-m-d'))->toBe('2026-01-03');
});

it('builds a deduplicated flow preview from consecutive ping segments', function () {
    $flow = [
        ['segment_type' => 'ring', 'to_dn' => '100', 'to_type' => 'extension'],
        ['segment_type' => 'ping', 'to_dn' => '100', 'to_type' => 'extension', 'to_name' => 'Alice', 'duration' => 5, 'answered' => false],
        ['segment_type' => 'ping', 'to_dn' => '100', 'to_type' => 'extension', 'to_name' => 'Alice', 'duration' => 3, 'answered' => true],
        ['segment_type' => 'ping', 'to_dn' => '200', 'to_type' => 'extension', 'to_name' => 'Bob', 'duration' => 2, 'answered' => false],
    ];

    $preview = PbxDataProcessor::buildFlowPreview($flow);

    expect($preview)->toHaveCount(2)
        ->and($preview[0]['to_dn'])->toBe('100')
        ->and($preview[0]['count'])->toBe(2)
        ->and($preview[0]['total_secs'])->toBe(8)
        ->and($preview[0]['answered'])->toBeTrue()
        ->and($preview[1]['to_dn'])->toBe('200')
        ->and($preview[1]['count'])->toBe(1);
});

it('resolves call-flow segment labels to translation keys', function () {
    expect(PbxDataProcessor::getSegmentLabel('ring', true))->toBe('expert-statistics::pbx.expert_statistics.cfa_segment_talk')
        ->and(PbxDataProcessor::getSegmentLabel('ring', false))->toBe('expert-statistics::pbx.expert_statistics.cfa_segment_ring')
        ->and(PbxDataProcessor::getSegmentLabel('pong', false))->toBe('expert-statistics::pbx.expert_statistics.cfa_segment_missed')
        ->and(PbxDataProcessor::getSegmentLabel('custom_type', false))->toBe('Custom_type')
        ->and(PbxDataProcessor::getSegmentLabel('', false))->toBe('expert-statistics::pbx.expert_statistics.cfa_segment_transit');
});

it('resolves call-flow step names from the pbx map', function () {
    $pbxMap = [
        'extensions' => ['100' => 'Alice'],
        'call_queues' => ['200' => ['name' => 'Support']],
    ];

    expect(PbxDataProcessor::resolveStepName('100', 'extension', null, $pbxMap))->toBe('Alice (100)')
        ->and(PbxDataProcessor::resolveStepName('999', 'extension', null, $pbxMap))->toBe('999')
        ->and(PbxDataProcessor::resolveStepName('200', 'queue', null, $pbxMap))->toBe('Support (200)')
        ->and(PbxDataProcessor::resolveStepName('100', 'extension', 'Explicit Name', $pbxMap))->toBe('Explicit Name')
        ->and(PbxDataProcessor::resolveStepName('', 'unknown', null, $pbxMap))->toBe('—');
});

it('resolves "DN — Name" display names from the pbx map', function () {
    $pbxMap = [
        'extensions' => ['100' => 'Alice'],
        'call_queues' => ['200' => ['name' => 'Support']],
    ];

    expect(PbxDataProcessor::resolveDisplayName('100', 'extension', $pbxMap))->toBe('100 — Alice')
        ->and(PbxDataProcessor::resolveDisplayName('200', 'queue', $pbxMap))->toBe('200 — Support')
        ->and(PbxDataProcessor::resolveDisplayName('999', 'extension', $pbxMap))->toBe('999');
});

it('formats the duration between two timestamps', function () {
    expect(PbxDataProcessor::formatDuration('2026-01-01 10:00:00', '2026-01-01 10:00:45'))->toBe('45s')
        ->and(PbxDataProcessor::formatDuration('2026-01-01 10:00:00', '2026-01-01 10:02:05'))->toBe('2m 5s')
        ->and(PbxDataProcessor::formatDuration('2026-01-01 10:00:00', 'not-a-date'))->toBe('0s');
});

it('formats a second count in short form', function () {
    expect(PbxDataProcessor::formatSecondsShort(45))->toBe('45s')
        ->and(PbxDataProcessor::formatSecondsShort(65))->toBe('1m 5s')
        ->and(PbxDataProcessor::formatSecondsShort(0))->toBe('0s');
});
