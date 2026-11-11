<?php

use CXEngine\ExpertStatistics\Support\CallAnalysisLink;
use CXEngine\ExpertStatistics\Support\QueueReportPeriods;
use CXEngine\ExpertStatistics\Tests\TestCase;

uses(TestCase::class);

it('offers the views the API allows for the range', function (?string $start, ?string $end, array $expected) {
    expect(QueueReportPeriods::available($start, $end))->toBe($expected);
})->with([
    'single day' => ['2026-03-05', '2026-03-05', ['total']],
    'two days' => ['2026-03-05', '2026-03-06', ['total', 'day']],
    'seven days over two ISO weeks' => ['2026-03-04', '2026-03-10', ['total', 'day']],
    'one full ISO week + 1 day' => ['2026-03-02', '2026-03-09', ['total', 'day', 'week']],
    'one calendar month' => ['2026-03-01', '2026-03-31', ['total', 'day', 'week']],
    'two partial months, under 28 days' => ['2026-03-20', '2026-04-10', ['total', 'day', 'week']],
    'two months' => ['2026-01-15', '2026-03-31', ['total', 'day', 'week', 'month']],
    'missing dates' => [null, '2026-03-31', ['total']],
    'reversed range' => ['2026-03-31', '2026-03-01', ['total']],
]);

it('accepts both day key formats', function () {
    expect(QueueReportPeriods::normalizeDate('2026-03-05'))->toBe('2026-03-05')
        ->and(QueueReportPeriods::normalizeDate('20260305'))->toBe('2026-03-05')
        ->and(QueueReportPeriods::normalizeDate('2026-W10'))->toBeNull()
        ->and(CallAnalysisLink::dayToDate('2026-03-05'))->toBe('2026-03-05')
        ->and(CallAnalysisLink::dayToDate('20260305'))->toBe('2026-03-05')
        ->and(CallAnalysisLink::dayToDate(null))->toBeNull();
});

it('scopes call analysis links to the row period', function () {
    expect(CallAnalysisLink::rowRange(['day' => '2026-03-02', 'period_start' => '2026-03-02', 'period_end' => '2026-03-08']))
        ->toBe(['2026-03-02', '2026-03-08'])
        ->and(CallAnalysisLink::rowRange(['day' => '2026-03-05']))->toBe(['2026-03-05', '2026-03-05'])
        ->and(CallAnalysisLink::rowRange(['queue' => '800']))->toBeNull();
});

it('labels periods in French', function () {
    app()->setLocale('fr');

    expect(QueueReportPeriods::label('month', '2026-01'))->toBe('Janvier 2026')
        ->and(QueueReportPeriods::label('week', '2026-W10', '2026-03-02', '2026-03-08'))->toBe('S10 · 02/03 – 08/03')
        ->and(QueueReportPeriods::label('day', '2026-03-05', '2026-03-05', '2026-03-05'))->toBe('05/03/2026')
        ->and(QueueReportPeriods::partialTooltip('2026-01-15', '2026-01-31'))->toBe('Période partielle : 15/01 – 31/01');
});

it('labels periods in English', function () {
    app()->setLocale('en');

    expect(QueueReportPeriods::label('month', '2026-01'))->toBe('January 2026')
        ->and(QueueReportPeriods::label('week', '2026-W10', '2026-03-02', '2026-03-08'))->toBe('W10 · 03/02 – 03/08')
        ->and(QueueReportPeriods::label('week', '2026-W10'))->toBe('W10 · 03/02 – 03/08')
        ->and(QueueReportPeriods::formatDay('20260305'))->toBe('03/05/2026')
        ->and(QueueReportPeriods::labels('month', [['key' => '2026-02', 'start' => '2026-02-01', 'end' => '2026-02-28']]))
        ->toBe(['2026-02' => 'February 2026']);
});

it('groups rows by period in order of appearance', function () {
    app()->setLocale('en');
    $rows = [
        ['period' => '2026-01', 'period_start' => '2026-01-15', 'period_end' => '2026-01-31', 'partial' => true, 'queue' => '800'],
        ['period' => '2026-01', 'period_start' => '2026-01-15', 'period_end' => '2026-01-31', 'partial' => true, 'queue' => '801'],
        ['period' => '2026-02', 'period_start' => '2026-02-01', 'period_end' => '2026-02-28', 'partial' => false, 'queue' => '800'],
    ];

    $groups = QueueReportPeriods::groupByPeriod('month', $rows);

    expect($groups)->toHaveCount(2)
        ->and($groups[0])->toMatchArray(['key' => '2026-01', 'label' => 'January 2026', 'start' => '2026-01-15', 'end' => '2026-01-31', 'partial' => true])
        ->and($groups[0]['rows'])->toHaveCount(2)
        ->and($groups[1]['rows'][0]['queue'])->toBe('800');
});

it('formats deltas per metric kind', function () {
    expect(QueueReportPeriods::formatDelta('calls', ['delta' => 30, 'delta_percent' => 12.4]))->toBe('+12 %')
        ->and(QueueReportPeriods::formatDelta('abandoned', ['delta' => 5, 'delta_percent' => null]))->toBe('+5')
        ->and(QueueReportPeriods::formatDelta('answer_rate', ['delta' => -3.24, 'delta_percent' => -4.1]))->toBe('−3.2 pts')
        ->and(QueueReportPeriods::formatDelta('avg_waiting', ['delta' => 8.4, 'delta_percent' => 30]))->toBe('+8 s')
        ->and(QueueReportPeriods::formatValue('answer_rate', 81.25))->toBe('81.3 %')
        ->and(QueueReportPeriods::formatValue('avg_waiting', 75))->toBe('1 min 15 s')
        ->and(QueueReportPeriods::formatValue('calls', null))->toBe('—');
});

it('builds badges coloured by assessment and weighted by severity', function () {
    app()->setLocale('en');
    $metric = fn (array $o) => array_merge([
        'current' => 120, 'previous' => 100, 'delta' => 20, 'delta_percent' => 20.0, 'normalized' => false,
        'trend' => 'up', 'assessment' => 'better', 'severity' => null, 'previous_period' => '2026-01',
    ], $o);

    $muted = QueueReportPeriods::badge('answered', $metric([]), 'month');
    $major = QueueReportPeriods::badge('answer_rate', $metric(['delta' => -12.5, 'trend' => 'down', 'assessment' => 'worse', 'severity' => 'major', 'normalized' => true]), 'month');
    $neutral = QueueReportPeriods::badge('calls', $metric(['assessment' => 'neutral', 'severity' => 'notable']), 'month', ['2026-01' => 'Jan (clipped)']);

    expect($muted['text'])->toBe('▲ +20 %')
        ->and($muted['class'])->toContain('text-gray-400')
        ->and($muted['title'])->toBe('Previous period (January 2026): 100')
        ->and($major['text'])->toBe('▼ −12.5 pts')
        ->and($major['class'])->toContain('bg-red-100')->toContain('ring-1')->toContain('font-semibold')
        ->and($major['title'])->toContain('compared per day')->toContain('major change')
        ->and($neutral['class'])->toContain('bg-amber-50')->not->toContain('ring-1')
        ->and($neutral['title'])->toContain('Jan (clipped)')
        ->and(QueueReportPeriods::badge('calls', null))->toBeNull()
        ->and(QueueReportPeriods::badge('answer_rate', $metric(['delta' => null, 'trend' => 'new'])))->toBeNull();
});

it('indexes the comparison by period, queue and element and filters anomalies by view', function () {
    $comparison = [[
        'queue' => '800', 'element' => '101', 'keyType' => '0', 'period' => '2026-02', 'previous_period' => '2026-01',
        'metrics' => ['calls' => ['current' => 5, 'previous' => 4, 'delta' => 1, 'score' => 0.1], 'avg_talking' => ['delta' => 3]],
    ]];

    $index = QueueReportPeriods::indexComparison($comparison);

    expect(array_keys($index))->toBe(['2026-02|800|101'])
        ->and($index['2026-02|800|101'])->toHaveKey('calls')->not->toHaveKey('avg_talking')
        ->and($index['2026-02|800|101']['calls'])->not->toHaveKey('score')
        ->and($index['2026-02|800|101']['calls']['previous_period'])->toBe('2026-01')
        ->and(QueueReportPeriods::seriesKey(['period' => '2026-02', 'queue' => '800', 'element' => '101']))->toBe('2026-02|800|101');

    $anomalies = [['keyType' => '4', 'metric' => 'calls'], ['keyType' => '0', 'metric' => 'answered']];
    expect(QueueReportPeriods::visibleAnomalies($anomalies, true))->toHaveCount(1)
        ->and(QueueReportPeriods::visibleAnomalies($anomalies, false))->toHaveCount(2);
});

it('averages talk per answered call and wait per waiting call without the message', function () {
    expect(QueueReportPeriods::averageTalking(['answered' => 4, 'talking_duration' => 600]))->toBe(150.0)
        ->and(QueueReportPeriods::averageTalking(['answered' => 0, 'talking_duration' => 0]))->toBeNull();

    // 10 calls waited (10 s message each), 6 answered by this queue, 4 overflowed elsewhere.
    $row = ['answered' => 6, 'waiting_duration' => 400, 'preanswer_duration' => 60, 'waiting_calls' => 10, 'net_waiting_duration' => 300];
    expect(QueueReportPeriods::averageWaiting($row))->toBe(30.0)
        ->and(QueueReportPeriods::averageWaiting(['waiting_calls' => 0, 'net_waiting_duration' => 0, 'answered' => 3]))->toBeNull();

    // Older API without the waiting population: (waiting − message) / answered.
    expect(QueueReportPeriods::averageWaiting(['answered' => 6, 'waiting_duration' => 400, 'preanswer_duration' => 60]))->toBe(56.7);
});
