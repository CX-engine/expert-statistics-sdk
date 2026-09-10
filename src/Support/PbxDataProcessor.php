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

    private static function calcTrend(float $current, float $previous): float
    {
        if ($previous == 0) {
            return 0.0;
        }

        return round(($current - $previous) / $previous * 100, 1);
    }
}
