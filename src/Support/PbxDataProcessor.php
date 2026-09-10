<?php

namespace CXEngine\ExpertStatistics\Support;

use Carbon\Carbon;

class PbxDataProcessor
{
    /**
     * Group raw hourly inbound records by calendar date and by hour-of-day.
     *
     * @param  array<int, array<string, mixed>>  $records
     * @return array{byDay: array<int, array<string, mixed>>, byHour: array<int, array<string, mixed>>}
     */
    public static function groupInboundByDayAndHour(array $records): array
    {
        $fields = [
            'inbound_calls',
            'answered_inbound_calls',
            'abandoned_within_preanswer',
            'total_talking_duration_in_seconds',
            'total_waiting_duration_in_seconds',
            'ten_seconds_calls',
        ];

        $byDay = [];
        $byHour = [];

        foreach ($records as $row) {
            $dt = Carbon::parse($row['hour']);
            $date = $dt->format('Y-m-d');
            $hour = $dt->format('H:00');

            if (! isset($byDay[$date])) {
                $byDay[$date] = array_merge(['date' => $date], array_fill_keys($fields, 0));
            }

            if (! isset($byHour[$hour])) {
                $byHour[$hour] = array_merge(['hour' => $hour], array_fill_keys($fields, 0));
            }

            foreach ($fields as $field) {
                $byDay[$date][$field] += (int) ($row[$field] ?? 0);
                $byHour[$hour][$field] += (int) ($row[$field] ?? 0);
            }
        }

        return [
            'byDay' => array_values($byDay),
            'byHour' => array_values($byHour),
        ];
    }

    /**
     * Group raw hourly outbound records by calendar date and by hour-of-day.
     *
     * @param  array<int, array<string, mixed>>  $records
     * @return array{byDay: array<int, array<string, mixed>>, byHour: array<int, array<string, mixed>>}
     */
    public static function groupOutboundByDayAndHour(array $records): array
    {
        $fields = [
            'outbound_calls',
            'answered_outbound_calls',
            'total_talking_duration_in_seconds',
        ];

        $byDay = [];
        $byHour = [];

        foreach ($records as $row) {
            $dt = Carbon::parse($row['hour']);
            $date = $dt->format('Y-m-d');
            $hour = $dt->format('H:00');

            if (! isset($byDay[$date])) {
                $byDay[$date] = array_merge(['date' => $date], array_fill_keys($fields, 0));
            }

            if (! isset($byHour[$hour])) {
                $byHour[$hour] = array_merge(['hour' => $hour], array_fill_keys($fields, 0));
            }

            foreach ($fields as $field) {
                $byDay[$date][$field] += (int) ($row[$field] ?? 0);
                $byHour[$hour][$field] += (int) ($row[$field] ?? 0);
            }
        }

        return [
            'byDay' => array_values($byDay),
            'byHour' => array_values($byHour),
        ];
    }

    /**
     * Calculate KPI aggregates from grouped inbound data.
     *
     * @param  array{byDay: array<int, array<string, mixed>>, byHour: array<int, array<string, mixed>>}  $grouped
     * @return array<string, mixed>
     */
    public static function calcInboundKpis(array $grouped): array
    {
        $byHour = $grouped['byHour'];

        $totalCalls = 0;
        $totalAnswered = 0;
        $totalAbandoned = 0;
        $totalWaiting = 0;
        $tenSecondsCalls = 0;
        $totalTalking = 0;
        $bestRateHour = null;
        $worstRateHour = null;
        $mostCallsHour = null;
        $bestAnsweredRate = 0.0;
        $worstAnsweredRate = 100.0;
        $mostCalls = 0;

        foreach ($byHour as $row) {
            $inbound = (int) $row['inbound_calls'];
            $answered = (int) $row['answered_inbound_calls'];
            $abandoned = (int) $row['abandoned_within_preanswer'];
            $waiting = (int) $row['total_waiting_duration_in_seconds'];
            $talking = (int) $row['total_talking_duration_in_seconds'];
            $ten = (int) $row['ten_seconds_calls'];

            $totalCalls += $inbound;
            $totalAnswered += $answered;
            $totalAbandoned += $abandoned;
            $totalWaiting += $waiting;
            $totalTalking += $talking;
            $tenSecondsCalls += $ten;

            $effective = $inbound - $abandoned;

            if ($effective > 0) {
                $rate = $answered / $effective * 100;

                if ($rate >= $bestAnsweredRate) {
                    $bestAnsweredRate = round($rate, 1);
                    $bestRateHour = $row['hour'];
                }

                if ($rate <= $worstAnsweredRate) {
                    $worstAnsweredRate = round($rate, 1);
                    $worstRateHour = $row['hour'];
                }
            }

            if ($inbound >= $mostCalls) {
                $mostCalls = $inbound;
                $mostCallsHour = $row['hour'];
            }
        }

        $lostCalls = $totalCalls - $totalAbandoned - $totalAnswered;
        $avgWaitSeconds = $totalAnswered > 0 ? round($totalWaiting / $totalAnswered, 1) : 0.0;

        return [
            'totalCalls' => $totalCalls,
            'totalAnswered' => $totalAnswered,
            'lostCalls' => max(0, $lostCalls),
            'avgWaitSeconds' => $avgWaitSeconds,
            'tenSecondsCalls' => $tenSecondsCalls,
            'totalTalkingSeconds' => $totalTalking,
            'bestRateHour' => $bestRateHour,
            'worstRateHour' => $worstRateHour,
            'mostCallsHour' => $mostCallsHour,
            'bestAnsweredRate' => $bestAnsweredRate,
            'worstAnsweredRate' => $worstAnsweredRate,
            'mostCalls' => $mostCalls,
        ];
    }

    /**
     * Calculate KPI aggregates from grouped outbound data.
     *
     * @param  array{byDay: array<int, array<string, mixed>>, byHour: array<int, array<string, mixed>>}  $grouped
     * @return array<string, mixed>
     */
    public static function calcOutboundKpis(array $grouped): array
    {
        $byHour = $grouped['byHour'];

        $totalCalls = 0;
        $totalAnswered = 0;
        $totalTalking = 0;
        $bestRateHour = null;
        $worstRateHour = null;
        $mostCallsHour = null;
        $bestAnsweredRate = 0.0;
        $worstAnsweredRate = 100.0;
        $mostCalls = 0;

        foreach ($byHour as $row) {
            $outbound = (int) $row['outbound_calls'];
            $answered = (int) $row['answered_outbound_calls'];
            $talking = (int) $row['total_talking_duration_in_seconds'];

            $totalCalls += $outbound;
            $totalAnswered += $answered;
            $totalTalking += $talking;

            if ($outbound > 0) {
                $rate = $answered / $outbound * 100;

                if ($rate >= $bestAnsweredRate) {
                    $bestAnsweredRate = round($rate, 1);
                    $bestRateHour = $row['hour'];
                }

                if ($rate <= $worstAnsweredRate) {
                    $worstAnsweredRate = round($rate, 1);
                    $worstRateHour = $row['hour'];
                }
            }

            if ($outbound >= $mostCalls) {
                $mostCalls = $outbound;
                $mostCallsHour = $row['hour'];
            }
        }

        $lostCalls = max(0, $totalCalls - $totalAnswered);

        return [
            'totalCalls' => $totalCalls,
            'totalAnswered' => $totalAnswered,
            'lostCalls' => $lostCalls,
            'totalTalkingSeconds' => $totalTalking,
            'bestRateHour' => $bestRateHour,
            'worstRateHour' => $worstRateHour,
            'mostCallsHour' => $mostCallsHour,
            'bestAnsweredRate' => $bestAnsweredRate,
            'worstAnsweredRate' => $worstAnsweredRate,
            'mostCalls' => $mostCalls,
        ];
    }

    /**
     * Build answered/unanswered chart series grouped by day, filling gaps.
     *
     * @param  array<int, array<string, mixed>>  $byDay
     * @return array{series: array<int, array<string, mixed>>, categories: array<int, string>}
     */
    public static function buildAnsweredChartByDay(array $byDay, string $startDate, string $endDate): array
    {
        $indexed = [];

        foreach ($byDay as $row) {
            $indexed[$row['date']] = $row;
        }

        $dates = [];
        $answered = [];
        $unanswered = [];

        foreach (self::generateDateRange($startDate, $endDate) as $date) {
            $key = $date->format('Y-m-d');
            $row = $indexed[$key] ?? [];
            $inbound = (int) ($row['inbound_calls'] ?? 0);
            $ans = (int) ($row['answered_inbound_calls'] ?? 0);
            $abandoned = (int) ($row['abandoned_within_preanswer'] ?? 0);
            $unans = max(0, $inbound - $abandoned - $ans);

            $dates[] = $key;
            $answered[] = $ans;
            $unanswered[] = $unans;
        }

        return [
            'series' => [
                ['name' => 'Answered', 'data' => $answered],
                ['name' => 'Unanswered', 'data' => $unanswered],
            ],
            'categories' => $dates,
        ];
    }

    /**
     * Build answered/unanswered chart series grouped by hour, filling gaps.
     *
     * @param  array<int, array<string, mixed>>  $byHour
     * @return array{series: array<int, array<string, mixed>>, categories: array<int, string>}
     */
    public static function buildAnsweredChartByHour(array $byHour, string $startTime, string $endTime): array
    {
        $indexed = [];

        foreach ($byHour as $row) {
            $indexed[$row['hour']] = $row;
        }

        $hours = [];
        $answered = [];
        $unanswered = [];

        $start = (int) substr($startTime, 0, 2);
        $end = (int) substr($endTime, 0, 2);

        for ($h = $start; $h <= $end; $h++) {
            $key = sprintf('%02d:00', $h);
            $row = $indexed[$key] ?? [];
            $inbound = (int) ($row['inbound_calls'] ?? 0);
            $ans = (int) ($row['answered_inbound_calls'] ?? 0);
            $abandoned = (int) ($row['abandoned_within_preanswer'] ?? 0);
            $unans = max(0, $inbound - $abandoned - $ans);

            $hours[] = $key;
            $answered[] = $ans;
            $unanswered[] = $unans;
        }

        return [
            'series' => [
                ['name' => 'Answered', 'data' => $answered],
                ['name' => 'Unanswered', 'data' => $unanswered],
            ],
            'categories' => $hours,
        ];
    }

    /**
     * Build answered/unanswered outbound chart by day, filling gaps.
     *
     * @param  array<int, array<string, mixed>>  $byDay
     * @return array{series: array<int, array<string, mixed>>, categories: array<int, string>}
     */
    public static function buildOutboundChartByDay(array $byDay, string $startDate, string $endDate): array
    {
        $indexed = [];

        foreach ($byDay as $row) {
            $indexed[$row['date']] = $row;
        }

        $dates = [];
        $answered = [];
        $unanswered = [];

        foreach (self::generateDateRange($startDate, $endDate) as $date) {
            $key = $date->format('Y-m-d');
            $row = $indexed[$key] ?? [];
            $outbound = (int) ($row['outbound_calls'] ?? 0);
            $ans = (int) ($row['answered_outbound_calls'] ?? 0);
            $unans = max(0, $outbound - $ans);

            $dates[] = $key;
            $answered[] = $ans;
            $unanswered[] = $unans;
        }

        return [
            'series' => [
                ['name' => 'Answered', 'data' => $answered],
                ['name' => 'Unanswered', 'data' => $unanswered],
            ],
            'categories' => $dates,
        ];
    }

    /**
     * Build answered/unanswered outbound chart series grouped by hour, filling gaps.
     *
     * @param  array<int, array<string, mixed>>  $byHour
     * @return array{series: array<int, array<string, mixed>>, categories: array<int, string>}
     */
    public static function buildOutboundChartByHour(array $byHour, string $startTime, string $endTime): array
    {
        $indexed = [];

        foreach ($byHour as $row) {
            $indexed[$row['hour']] = $row;
        }

        $hours = [];
        $answered = [];
        $unanswered = [];

        $start = (int) substr($startTime, 0, 2);
        $end = (int) substr($endTime, 0, 2);

        for ($h = $start; $h <= $end; $h++) {
            $key = sprintf('%02d:00', $h);
            $row = $indexed[$key] ?? [];
            $outbound = (int) ($row['outbound_calls'] ?? 0);
            $ans = (int) ($row['answered_outbound_calls'] ?? 0);
            $unans = max(0, $outbound - $ans);

            $hours[] = $key;
            $answered[] = $ans;
            $unanswered[] = $unans;
        }

        return [
            'series' => [
                ['name' => 'Answered', 'data' => $answered],
                ['name' => 'Unanswered', 'data' => $unanswered],
            ],
            'categories' => $hours,
        ];
    }

    /**
     * Build top-10 users horizontal bar chart data.
     *
     * @param  array<int, array<string, mixed>>  $usersData
     * @return array{series: array<int, array<string, mixed>>, labels: array<int, string>}
     */
    public static function buildTopUsersChart(array $usersData): array
    {
        usort($usersData, fn ($a, $b) => (int) $b['total_calls'] <=> (int) $a['total_calls']);

        $top = array_slice($usersData, 0, 10);

        $inbound = [];
        $outbound = [];
        $internal = [];
        $labels = [];

        foreach ($top as $user) {
            $labels[] = (string) $user['display_name'];
            $inbound[] = (int) $user['inbound_calls'];
            $outbound[] = (int) $user['outbound_calls'];
            $internal[] = (int) $user['internal_calls_made'] + (int) $user['internal_calls_received'];
        }

        return [
            'series' => [
                ['name' => 'Inbound', 'data' => $inbound],
                ['name' => 'Outbound', 'data' => $outbound],
                ['name' => 'Internal', 'data' => $internal],
            ],
            'labels' => $labels,
        ];
    }

    /**
     * Parse unique-calls-period response into totals per direction.
     *
     * @param  array<int, array<string, mixed>>  $data
     * @return array{inbound: array{calls: int, duration: int}, outbound: array{calls: int, duration: int}, internal: array{calls: int, duration: int}}
     */
    public static function parsePbxTotals(array $data): array
    {
        $totals = [
            'inbound' => ['calls' => 0, 'duration' => 0],
            'outbound' => ['calls' => 0, 'duration' => 0],
            'internal' => ['calls' => 0, 'duration' => 0],
        ];

        foreach ($data as $row) {
            $way = $row['call_way'] ?? '';

            if (! isset($totals[$way])) {
                continue;
            }

            $totals[$way]['calls'] += (int) ($row['unique_calls'] ?? 0);
            $totals[$way]['duration'] += (int) ($row['total_duration_seconds'] ?? 0);
        }

        return $totals;
    }

    /**
     * Build ApexCharts-ready trend series from monthly trend API data.
     *
     * @param  array<int, array<string, mixed>>  $trendData
     * @return array{answered: array{series: array<int, array<string, mixed>>, labels: array<int, string>}, waitTime: array{series: array<int, array<string, mixed>>, labels: array<int, string>}}
     */
    public static function buildTrendCharts(array $trendData): array
    {
        usort($trendData, fn ($a, $b) => strcmp((string) $a['month'], (string) $b['month']));

        $labels = [];
        $totalCalls = [];
        $answeredCalls = [];
        $answerRates = [];
        $waitTimes = [];

        foreach ($trendData as $row) {
            $month = Carbon::parse($row['month'])->translatedFormat('M Y');
            $total = (int) ($row['inbound_calls'] ?? 0);
            $answered = (int) ($row['answered_inbound_calls'] ?? 0);
            $rate = $total > 0 ? round($answered / $total * 100, 1) : 0.0;
            $wait = (float) ($row['avg_waiting_duration_in_seconds'] ?? 0);

            $labels[] = $month;
            $totalCalls[] = $total;
            $answeredCalls[] = $answered;
            $answerRates[] = $rate;
            $waitTimes[] = $wait;
        }

        return [
            'answered' => [
                'series' => [
                    ['name' => 'Total Calls', 'type' => 'column', 'data' => $totalCalls],
                    ['name' => 'Answered Calls', 'type' => 'line', 'data' => $answeredCalls],
                    ['name' => 'Answer Rate %', 'type' => 'line', 'data' => $answerRates],
                ],
                'labels' => $labels,
            ],
            'waitTime' => [
                'series' => [
                    ['name' => 'Average Wait Time', 'data' => $waitTimes],
                ],
                'labels' => $labels,
            ],
        ];
    }

    /**
     * Calculate current-month vs previous-month trend KPIs.
     *
     * @param  array<int, array<string, mixed>>  $trendData
     * @return array<string, mixed>
     */
    public static function calcTrends(array $trendData): array
    {
        $indexed = [];

        foreach ($trendData as $row) {
            $key = Carbon::parse($row['month'])->format('Y-m');
            $indexed[$key] = $row;
        }

        $currentKey = Carbon::now()->format('Y-m');
        $previousKey = Carbon::now()->subMonth()->format('Y-m');

        $current = $indexed[$currentKey] ?? null;

        if ($current === null && ! empty($indexed)) {
            $currentKey = max(array_keys($indexed));
            $current = $indexed[$currentKey];
            $previousKey = Carbon::parse($currentKey.'-01')->subMonth()->format('Y-m');
        }

        $previous = $indexed[$previousKey] ?? null;

        $totalCalls = (int) ($current['inbound_calls'] ?? 0);
        $prevTotalCalls = (int) ($previous['inbound_calls'] ?? 0);
        $answeredCalls = (int) ($current['answered_inbound_calls'] ?? 0);
        $prevAnsweredCalls = (int) ($previous['answered_inbound_calls'] ?? 0);
        $avgWait = (float) ($current['avg_waiting_duration_in_seconds'] ?? 0);
        $prevAvgWait = (float) ($previous['avg_waiting_duration_in_seconds'] ?? 0);

        $carCalls = $totalCalls > 0 ? round($answeredCalls / $totalCalls * 100, 1) : 0.0;
        $prevCarCalls = $prevTotalCalls > 0 ? round($prevAnsweredCalls / $prevTotalCalls * 100, 1) : 0.0;

        $totalTrend = self::calcTrend($totalCalls, $prevTotalCalls);
        $carTrend = self::calcTrend($carCalls, $prevCarCalls);
        $waitTimeTrend = self::calcTrend($avgWait, $prevAvgWait);

        return [
            'totalCalls' => $totalCalls,
            'prevTotalCalls' => $prevTotalCalls,
            'carCalls' => $carCalls,
            'prevCarCalls' => $prevCarCalls,
            'averageWaitTime' => $avgWait,
            'prevAverageWaitTime' => $prevAvgWait,
            'totalTrend' => $totalTrend,
            'carTrend' => $carTrend,
            'waitTimeTrend' => $waitTimeTrend,
            'currentMonthName' => Carbon::parse($currentKey.'-01')->translatedFormat('F'),
            'lastMonthName' => Carbon::parse($previousKey.'-01')->translatedFormat('F'),
            'currentYear' => Carbon::parse($currentKey.'-01')->format('Y'),
        ];
    }

    public static function formatSecsToHourMin(int $seconds): string
    {
        if ($seconds <= 0) {
            return '0min';
        }

        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        if ($hours > 0 && $minutes > 0) {
            return "{$hours}h {$minutes}min";
        }

        if ($hours > 0) {
            return "{$hours}h";
        }

        return "{$minutes}min";
    }

    public static function formatSecsToMinSec(int $seconds): string
    {
        if ($seconds <= 0) {
            return '0:00';
        }

        $minutes = intdiv($seconds, 60);
        $secs = $seconds % 60;

        return sprintf('%d:%02d', $minutes, $secs);
    }

    /**
     * Build a deduplicated preview of a call flow, collapsing consecutive
     * "ping" segments to the same destination into a single entry with an
     * accumulated count/duration.
     *
     * @param  array<int, array<string, mixed>>  $flow
     * @return array<int, array<string, mixed>>
     */
    public static function buildFlowPreview(array $flow): array
    {
        $preview = [];

        foreach ($flow as $step) {
            if (($step['segment_type'] ?? '') !== 'ping') {
                continue;
            }

            $key = ($step['to_dn'] ?? '').':'.($step['to_type'] ?? '');
            $last = count($preview) - 1;
            $stepSecs = (int) ($step['duration'] ?? 0);

            if ($last >= 0 && $preview[$last]['key'] === $key) {
                $preview[$last]['count']++;
                $preview[$last]['total_secs'] += $stepSecs;

                if ($step['answered'] ?? false) {
                    $preview[$last]['answered'] = true;
                }
            } else {
                $preview[] = [
                    'key' => $key,
                    'to_dn' => $step['to_dn'] ?? '',
                    'to_type' => $step['to_type'] ?? '',
                    'to_name' => $step['to_name'] ?? null,
                    'count' => 1,
                    'total_secs' => $stepSecs,
                    'answered' => $step['answered'] ?? false,
                ];
            }
        }

        return $preview;
    }

    /**
     * Resolve the translation key for a call-flow segment label. Returns the
     * key itself (not the translated text) - this class has no translation
     * dependency elsewhere, so callers should translate with __() at the
     * view layer, e.g. __(PbxDataProcessor::getSegmentLabel(...)).
     */
    public static function getSegmentLabel(string $segmentType, bool $answered): string
    {
        if ($answered) {
            return 'expert-statistics::pbx.expert_statistics.cfa_segment_talk';
        }

        return match ($segmentType) {
            'ring', 'ringing' => 'expert-statistics::pbx.expert_statistics.cfa_segment_ring',
            'pong' => 'expert-statistics::pbx.expert_statistics.cfa_segment_missed',
            'transfer', 'transferred' => 'expert-statistics::pbx.expert_statistics.cfa_segment_transfer',
            'hold' => 'expert-statistics::pbx.expert_statistics.cfa_segment_hold',
            'voicemail' => 'expert-statistics::pbx.expert_statistics.cfa_segment_voicemail',
            'ivr' => 'expert-statistics::pbx.expert_statistics.cfa_segment_ivr',
            'queue' => 'expert-statistics::pbx.expert_statistics.cfa_segment_queue',
            default => $segmentType !== '' ? ucfirst($segmentType) : 'expert-statistics::pbx.expert_statistics.cfa_segment_transit',
        };
    }

    /**
     * Resolve a human-readable name for a call-flow step's destination DN.
     *
     * @param  array<string, mixed>  $pbxMap  The 'extensions'/'call_queues' map from ExpertStatisticsService::getMap()
     */
    public static function resolveStepName(string $dn, string $type, ?string $name, array $pbxMap): string
    {
        if ($name) {
            return $name;
        }

        if ($type === 'extension') {
            $extName = $pbxMap['extensions'][$dn] ?? null;

            return $extName ? "{$extName} ({$dn})" : $dn;
        }

        if ($type === 'queue') {
            $q = $pbxMap['call_queues'][$dn] ?? null;
            $qName = is_array($q) ? ($q['name'] ?? null) : null;

            return $qName ? "{$qName} ({$dn})" : $dn;
        }

        return $dn ?: '—';
    }

    /**
     * Resolve a "DN — Name" display label for a filter/selector element.
     *
     * @param  array<string, mixed>  $pbxMap  The 'extensions'/'call_queues' map from ExpertStatisticsService::getMap()
     */
    public static function resolveDisplayName(string $dn, string $type, array $pbxMap): string
    {
        if ($type === 'queue') {
            $q = $pbxMap['call_queues'][$dn] ?? null;

            if ($q && is_array($q) && isset($q['name'])) {
                return $dn.' — '.$q['name'];
            }
        }

        if ($type === 'extension') {
            $name = $pbxMap['extensions'][$dn] ?? null;

            if ($name) {
                return $dn.' — '.$name;
            }
        }

        return $dn;
    }

    /**
     * Format the elapsed time between two timestamps as "Xm Ys" (or "Ys"
     * under a minute). Unlike formatSecsToMinSec() (colon-separated "M:SS"
     * from a raw second count), this derives the duration from two
     * datetime strings, matching the Call Flow Analysis call-list display.
     */
    public static function formatDuration(string $startedAt, string $endedAt): string
    {
        $startTimestamp = strtotime($startedAt);
        $endTimestamp = strtotime($endedAt);
        $seconds = ($startTimestamp !== false && $endTimestamp !== false) ? max(0, $endTimestamp - $startTimestamp) : 0;
        $minutes = intdiv($seconds, 60);
        $remaining = $seconds % 60;

        return $minutes > 0 ? "{$minutes}m {$remaining}s" : "{$remaining}s";
    }

    /**
     * Format a second count as "Xm Ys" (or "Ys" under a minute). Unlike
     * formatSecsToMinSec() (colon-separated "M:SS"), used for the compact
     * Call Flow Analysis segment/step duration labels.
     */
    public static function formatSecondsShort(int $seconds): string
    {
        if ($seconds >= 60) {
            return intdiv($seconds, 60).'m '.($seconds % 60).'s';
        }

        return $seconds.'s';
    }

    /**
     * Generate an inclusive date range.
     *
     * @return array<int, Carbon>
     */
    public static function generateDateRange(string $startDate, string $endDate): array
    {
        $dates = [];
        $current = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();

        while ($current->lte($end)) {
            $dates[] = $current->copy();
            $current->addDay();
        }

        return $dates;
    }

    // --- Agent Monitoring ---
    // Shared by the three Agent Monitoring pages (Realtime Status, Queue
    // Connection, Status Breakdown). Queue Connection and Status Breakdown's
    // raw API rows both boil down to a (agent DN, grouping key, minutes)
    // shape once resolved through a caller-supplied minutes closure, so the
    // grouping/pivoting helpers below are shared between the two.

    /**
     * Categorize realtime agent-status rows into online/away/unavailable
     * counts. "Online" = PBX-registered with status code 0, "away" =
     * registered with status code 4, everything else (unregistered, or
     * registered with any other status code) counts as "unavailable".
     *
     * @param  array<int, array<string, mixed>>  $agentData  Rows from ExpertStatisticsService::getAgentStatsRealtimeStatus()['data'].
     * @return array{online: int, away: int, unavailable: int, total: int}
     */
    public static function agentMonitoringTotalCounts(array $agentData): array
    {
        $online = 0;
        $away = 0;
        $unavailable = 0;

        foreach ($agentData as $agent) {
            $registered = $agent['registration_status']['pbx_registered'] ?? false;
            $code = (int) ($agent['current_status']['code'] ?? -1);

            if ($registered && $code === 0) {
                $online++;
            } elseif ($registered && $code === 4) {
                $away++;
            } else {
                $unavailable++;
            }
        }

        return [
            'online' => $online,
            'away' => $away,
            'unavailable' => $unavailable,
            'total' => count($agentData),
        ];
    }

    /**
     * Build the realtime-status threshold alerts (long connection, long
     * disconnection, extended unavailability, extended away), skipping any
     * whose id is already in $dismissedAlertIds. Title/message are returned
     * as translation keys + params rather than translated text (see
     * getSegmentLabel() above) - callers translate at the view/component
     * layer. $unknownAgentLabel is the one piece of translated text this
     * needs up front, to build the ":agent" substitution value itself.
     *
     * @param  array<int, array<string, mixed>>  $agentData  Rows from ExpertStatisticsService::getAgentStatsRealtimeStatus()['data'].
     * @param  array<string, string>  $extensionsMap  DN => display name, from ExpertStatisticsService::getMap()['extensions'].
     * @param  array<int, string>  $dismissedAlertIds
     * @return array<int, array{id: string, type: string, severity: string, titleKey: string, messageKey: string, messageParams: array<string, string>}>
     */
    public static function agentMonitoringAlerts(array $agentData, array $extensionsMap, array $dismissedAlertIds, string $unknownAgentLabel): array
    {
        $thresholds = [
            'long_connected' => 600,
            'long_disconnected' => 960,
            'long_unavailable' => 60,
            'very_long_unavailable' => 120,
            'long_away' => 1440,
        ];

        $alerts = [];

        foreach ($agentData as $agent) {
            $dn = (string) ($agent['user_dn'] ?? '');
            $agentLabel = $dn.' — '.($extensionsMap[$dn] ?? $unknownAgentLabel);
            $registered = $agent['registration_status']['pbx_registered'] ?? false;
            $regMinutes = (int) ($agent['registration_status']['duration']['minutes'] ?? 0);
            $regFormatted = (string) ($agent['registration_status']['duration']['formatted'] ?? '');
            $statusCode = (int) ($agent['current_status']['code'] ?? -1);
            $statusName = (string) ($agent['current_status']['name'] ?? '');
            $statusMinutes = (int) ($agent['status_duration']['minutes'] ?? 0);
            $statusFormatted = (string) ($agent['status_duration']['formatted'] ?? '');

            if ($registered && $regMinutes > $thresholds['long_connected']) {
                $id = "connect-{$dn}";
                if (! in_array($id, $dismissedAlertIds, true)) {
                    $alerts[] = [
                        'id' => $id,
                        'type' => 'warning',
                        'severity' => 'medium',
                        'titleKey' => 'expert-statistics::pbx.expert_statistics.agent_monitoring_alert_long_connected',
                        'messageKey' => 'expert-statistics::pbx.expert_statistics.agent_monitoring_alert_long_connected_msg',
                        'messageParams' => ['agent' => $agentLabel, 'duration' => $regFormatted],
                    ];
                }
            }

            if (! $registered && $regMinutes > $thresholds['long_disconnected']) {
                $id = "disconnect-{$dn}";
                if (! in_array($id, $dismissedAlertIds, true)) {
                    $alerts[] = [
                        'id' => $id,
                        'type' => 'error',
                        'severity' => $regMinutes > $thresholds['very_long_unavailable'] ? 'high' : 'medium',
                        'titleKey' => 'expert-statistics::pbx.expert_statistics.agent_monitoring_alert_long_disconnected',
                        'messageKey' => 'expert-statistics::pbx.expert_statistics.agent_monitoring_alert_long_disconnected_msg',
                        'messageParams' => ['agent' => $agentLabel, 'duration' => $regFormatted],
                    ];
                }
            }

            if ($registered && $statusCode !== 0 && $statusMinutes > $thresholds['long_unavailable']) {
                $id = "unavailable-{$dn}";
                if (! in_array($id, $dismissedAlertIds, true)) {
                    $alerts[] = [
                        'id' => $id,
                        'type' => 'warning',
                        'severity' => $statusMinutes > $thresholds['very_long_unavailable'] ? 'high' : 'medium',
                        'titleKey' => 'expert-statistics::pbx.expert_statistics.agent_monitoring_alert_long_unavailable',
                        'messageKey' => 'expert-statistics::pbx.expert_statistics.agent_monitoring_alert_long_unavailable_msg',
                        'messageParams' => ['agent' => $agentLabel, 'status' => strtolower($statusName), 'duration' => $statusFormatted],
                    ];
                }
            }

            if ($statusCode === 4 && $statusMinutes > $thresholds['long_away']) {
                $id = "long-away-{$dn}";
                if (! in_array($id, $dismissedAlertIds, true)) {
                    $alerts[] = [
                        'id' => $id,
                        'type' => 'warning',
                        'severity' => 'medium',
                        'titleKey' => 'expert-statistics::pbx.expert_statistics.agent_monitoring_alert_extended_away',
                        'messageKey' => 'expert-statistics::pbx.expert_statistics.agent_monitoring_alert_extended_away_msg',
                        'messageParams' => ['agent' => $agentLabel, 'duration' => $statusFormatted],
                    ];
                }
            }
        }

        return $alerts;
    }

    /**
     * Group flat agent-monitoring rows (one row per agent/key slice, e.g.
     * one Queue Connection or Status Breakdown API row) by an agent DN
     * field, summing a derived minute value per distinct value of a
     * grouping field (queue_dn for Queue Connection, status code for Status
     * Breakdown) and for the agent's grand total.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @param  \Closure(array<string, mixed>): int  $minutesResolver  Extracts the minute value from one row.
     * @return array<int, array{user_dn: string, items: array<int, array{key: string, minutes: int}>, total_minutes: int}>  Sorted by total_minutes, descending; items sorted by minutes, descending.
     */
    public static function agentMonitoringGroupByAgent(array $rows, string $dnField, string $keyField, \Closure $minutesResolver): array
    {
        $byAgent = [];

        foreach ($rows as $row) {
            $dn = (string) ($row[$dnField] ?? '');
            $key = (string) ($row[$keyField] ?? '');
            $minutes = $minutesResolver($row);

            $byAgent[$dn] ??= ['user_dn' => $dn, 'items' => [], 'total_minutes' => 0];
            $byAgent[$dn]['items'][$key] = ($byAgent[$dn]['items'][$key] ?? 0) + $minutes;
            $byAgent[$dn]['total_minutes'] += $minutes;
        }

        $agents = [];
        foreach ($byAgent as $agent) {
            $items = [];
            foreach ($agent['items'] as $key => $minutes) {
                $items[] = ['key' => (string) $key, 'minutes' => $minutes];
            }
            usort($items, fn (array $a, array $b): int => $b['minutes'] <=> $a['minutes']);

            $agents[] = ['user_dn' => $agent['user_dn'], 'items' => $items, 'total_minutes' => $agent['total_minutes']];
        }

        usort($agents, fn (array $a, array $b): int => $b['total_minutes'] <=> $a['total_minutes']);

        return $agents;
    }

    /**
     * Sum a derived minute value across all rows, grouped by a key field
     * (queue_dn for Queue Connection, status code for Status Breakdown).
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @param  \Closure(array<string, mixed>): int  $minutesResolver
     * @return array<int, array{key: string, minutes: int}>  Sorted by minutes, descending.
     */
    public static function agentMonitoringAggregateByKey(array $rows, string $keyField, \Closure $minutesResolver): array
    {
        $byKey = [];

        foreach ($rows as $row) {
            $key = (string) ($row[$keyField] ?? '');
            $byKey[$key] = ($byKey[$key] ?? 0) + $minutesResolver($row);
        }

        $items = [];
        foreach ($byKey as $key => $minutes) {
            $items[] = ['key' => (string) $key, 'minutes' => $minutes];
        }

        usort($items, fn (array $a, array $b): int => $b['minutes'] <=> $a['minutes']);

        return $items;
    }

    /**
     * Group flat agent-monitoring rows by calendar date, summing a derived
     * minute value per distinct key within each date. Used to build the
     * "per day" stacked-bar charts (optionally after filtering rows down to
     * one agent first, for the per-agent daily small-multiples).
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @param  \Closure(array<string, mixed>): int  $minutesResolver
     * @return array<int, array{label: string, items: array<int, array{key: string, minutes: int}>}>  Sorted by date, ascending; label is "d/m".
     */
    public static function agentMonitoringGroupByDate(array $rows, string $dateField, string $keyField, \Closure $minutesResolver): array
    {
        $byDate = [];

        foreach ($rows as $row) {
            $date = (string) ($row[$dateField] ?? '');

            if ($date === '') {
                continue;
            }

            $key = (string) ($row[$keyField] ?? '');
            $minutes = $minutesResolver($row);

            $byDate[$date] ??= [];
            $byDate[$date][$key] = ($byDate[$date][$key] ?? 0) + $minutes;
        }

        ksort($byDate);

        $buckets = [];
        foreach ($byDate as $date => $keyMinutes) {
            $items = [];
            foreach ($keyMinutes as $key => $minutes) {
                $items[] = ['key' => (string) $key, 'minutes' => $minutes];
            }

            $buckets[] = ['label' => Carbon::parse($date)->format('d/m'), 'items' => $items];
        }

        return $buckets;
    }

    /**
     * Pivot a list of {label, items:[{key,minutes}]} buckets (from
     * agentMonitoringGroupByAgent()'s per-agent items, or
     * agentMonitoringGroupByDate()) into ApexCharts-ready categories + one
     * series per distinct key.
     *
     * @param  array<int, array{label: string, items: array<int, array{key: string, minutes: int}>}>  $buckets
     * @param  \Closure(string): string  $nameResolver  Resolves a key to its display name.
     * @param  \Closure(string): string  $colorResolver  Resolves a key to its series color.
     * @return array{categories: array<int, string>, series: array<int, array<string, mixed>>}
     */
    public static function agentMonitoringBuildStackedSeries(array $buckets, \Closure $nameResolver, \Closure $colorResolver): array
    {
        $keys = [];
        foreach ($buckets as $bucket) {
            foreach ($bucket['items'] as $item) {
                if (! in_array($item['key'], $keys, true)) {
                    $keys[] = $item['key'];
                }
            }
        }
        sort($keys);

        $categories = array_column($buckets, 'label');

        $series = [];
        foreach ($keys as $key) {
            $data = [];

            foreach ($buckets as $bucket) {
                $minutes = 0;
                foreach ($bucket['items'] as $item) {
                    if ($item['key'] === $key) {
                        $minutes = $item['minutes'];
                        break;
                    }
                }
                $data[] = $minutes;
            }

            $series[] = [
                'name' => $nameResolver($key),
                'data' => $data,
                'color' => $colorResolver($key),
            ];
        }

        return ['categories' => $categories, 'series' => $series];
    }

    /**
     * Build ApexCharts heatmap series: one row (series) per agent group from
     * agentMonitoringGroupByAgent(), one column per key in $keys, cell value
     * = that agent's summed minutes for that key (0 when absent).
     *
     * @param  array<int, array{user_dn: string, items: array<int, array{key: string, minutes: int}>, total_minutes: int}>  $agentGroups
     * @param  array<int, string>  $keys  Column keys, in display order.
     * @param  \Closure(string): string  $agentNameResolver
     * @param  \Closure(string): string  $keyNameResolver
     * @return array<int, array{name: string, data: array<int, array{x: string, y: int}>}>
     */
    public static function agentMonitoringBuildHeatmapSeries(array $agentGroups, array $keys, \Closure $agentNameResolver, \Closure $keyNameResolver): array
    {
        $series = [];

        foreach ($agentGroups as $agent) {
            $data = [];

            foreach ($keys as $key) {
                $minutes = 0;
                foreach ($agent['items'] as $item) {
                    if ($item['key'] === $key) {
                        $minutes = $item['minutes'];
                        break;
                    }
                }

                $data[] = ['x' => $keyNameResolver($key), 'y' => $minutes];
            }

            $series[] = ['name' => $agentNameResolver($agent['user_dn']), 'data' => $data];
        }

        return $series;
    }

    /**
     * Collect the distinct string values of a key field across a list of
     * raw rows, sorted ascending. Used to derive stable heatmap columns /
     * palette assignment order.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, string>
     */
    public static function agentMonitoringDistinctKeys(array $rows, string $keyField): array
    {
        $keys = [];

        foreach ($rows as $row) {
            $key = (string) ($row[$keyField] ?? '');
            if (! in_array($key, $keys, true)) {
                $keys[] = $key;
            }
        }

        sort($keys);

        return $keys;
    }

    /**
     * Flatten the nested Status Breakdown "daily activity" API response
     * (one entry per agent, each holding a list of {date, status_breakdown}
     * days) into flat {date, user_dn, status, total_minutes} rows, suitable
     * for agentMonitoringGroupByDate() / agentMonitoringGroupByAgent().
     *
     * @param  array<int, array<string, mixed>>  $dailyActivityAgents  Rows from ExpertStatisticsService::getAgentStatsDailyActivity()['data'].
     * @return array<int, array{date: string, user_dn: string, status: string, total_minutes: int}>
     */
    public static function flattenAgentMonitoringDailyActivity(array $dailyActivityAgents): array
    {
        $rows = [];

        foreach ($dailyActivityAgents as $agent) {
            $dn = (string) ($agent['user_dn'] ?? '');

            foreach ($agent['daily_activity'] ?? [] as $day) {
                $date = (string) ($day['date'] ?? '');

                if ($date === '') {
                    continue;
                }

                foreach ($day['status_breakdown'] ?? [] as $status) {
                    $rows[] = [
                        'date' => $date,
                        'user_dn' => $dn,
                        'status' => (string) ($status['status'] ?? -1),
                        'total_minutes' => (int) ($status['total_minutes'] ?? 0),
                    ];
                }
            }
        }

        return $rows;
    }

    private static function calcTrend(float $current, float $previous): float
    {
        if ($previous == 0) {
            return 0.0;
        }

        return round(($current - $previous) / $previous * 100, 1);
    }
}
