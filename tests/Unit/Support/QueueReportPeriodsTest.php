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

it('formats values per metric kind', function () {
    expect(QueueReportPeriods::formatValue('answer_rate', 81.25))->toBe('81.3 %')
        ->and(QueueReportPeriods::formatValue('avg_waiting', 75))->toBe('1 min 15 s')
        ->and(QueueReportPeriods::formatValue('abandoned', 17.6))->toBe('18')
        ->and(QueueReportPeriods::formatValue('calls', null))->toBe('—');
});

it('marks only the values notably worse or better than usual', function () {
    app()->setLocale('en');
    $metric = fn (array $o) => array_merge(['current' => 70.0, 'usual' => 88.0, 'assessment' => 'worse', 'severity' => 'major'], $o);

    $worse = QueueReportPeriods::usualMarker('answer_rate', $metric([]));
    $better = QueueReportPeriods::usualMarker('avg_waiting', $metric(['current' => 20, 'usual' => 45, 'assessment' => 'better', 'severity' => 'notable']));

    expect($worse['text'])->toBe('▼ usually 88.0 %')
        ->and($worse['class'])->toContain('text-red-600')->toContain('font-semibold')
        ->and($worse['title'])->toBe('Answer rate worse than usual (usually 88.0 %)')
        ->and($better['text'])->toBe('▼ usually 45 s')
        ->and($better['class'])->toContain('text-green-600')->not->toContain('font-semibold')
        ->and(QueueReportPeriods::usualMarker('answer_rate', $metric(['severity' => null])))->toBeNull()
        ->and(QueueReportPeriods::usualMarker('calls', $metric(['assessment' => 'neutral'])))->toBeNull()
        ->and(QueueReportPeriods::usualMarker('answer_rate', null))->toBeNull();
});

it('summarises an alert as its worse indicators and the call volume', function () {
    app()->setLocale('fr');
    $summary = QueueReportPeriods::anomalySummary([
        'issues' => [
            'answer_rate' => ['current' => 72.0, 'usual' => 88.0],
            'avg_waiting' => ['current' => 100.0, 'usual' => 35.0],
        ],
        'context' => ['calls' => ['delta_percent' => 34.6]],
    ]);

    expect($summary['issues'])->toBe([
        'Taux de réponse 72.0 % (habituellement 88.0 %)',
        'Attente moyenne 1 min 40 s (habituellement 35 s)',
    ])->and($summary['context'])->toBe('35 % d\'appels de plus que d\'habitude')
        ->and(QueueReportPeriods::anomalySummary(['issues' => [], 'context' => []])['context'])->toBeNull();
});

it('indexes the comparison by period, queue and element and filters the alerts by view', function () {
    $comparison = [[
        'queue' => '800', 'element' => '101', 'keyType' => '0', 'period' => '2026-02',
        'metrics' => [
            'answer_rate' => ['current' => 70.0, 'usual' => 88.0, 'assessment' => 'worse', 'severity' => 'major', 'score' => 1.8, 'delta' => -18],
            'calls' => ['current' => 5, 'usual' => 4],
        ],
    ]];

    $index = QueueReportPeriods::indexComparison($comparison);

    expect(array_keys($index))->toBe(['2026-02|800|101'])
        ->and($index['2026-02|800|101'])->toHaveKey('answer_rate')->not->toHaveKey('calls')
        ->and($index['2026-02|800|101']['answer_rate'])->toBe(['current' => 70.0, 'usual' => 88.0, 'assessment' => 'worse', 'severity' => 'major'])
        ->and(QueueReportPeriods::seriesKey(['period' => '2026-02', 'queue' => '800', 'element' => '101']))->toBe('2026-02|800|101');

    $anomalies = [['keyType' => '4', 'issues' => ['answer_rate' => []]], ['keyType' => '0', 'issues' => ['abandoned' => []]], ['keyType' => '4']];
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
