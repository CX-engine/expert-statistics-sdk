<?php

namespace CXEngine\ExpertStatistics\Support;

use Carbon\CarbonImmutable;

/**
 * Pure shaping of the My Queues report's period views (total / day / week / month) and of
 * the period-over-period comparison returned by `queues-report-aggregated` when it gets a
 * `period` parameter (expert-stats QueueReportPeriodBreakdown / QueueReportPeriodComparison).
 *
 * Availability rules mirror the API's QueueReportPeriodBreakdown::availablePeriods() so the
 * buttons are right before the first answer; the component trusts `available_periods` after.
 */
class QueueReportPeriods
{
    public const TOTAL = 'total';

    public const DAY = 'day';

    public const WEEK = 'week';

    public const MONTH = 'month';

    public const ALL = [self::TOTAL, self::DAY, self::WEEK, self::MONTH];

    public const BUCKETED = [self::DAY, self::WEEK, self::MONTH];

    /** Comparison metrics shown as badges in the table, keyed by the API metric name. */
    public const BADGE_METRICS = ['calls', 'answered', 'abandoned', 'answer_rate', 'avg_waiting'];

    /** Every metric the API compares, in display order (anomalies panel). */
    public const METRICS = ['calls', 'answered', 'abandoned', 'answer_rate', 'avg_waiting', 'avg_talking'];

    private const VOLUME_METRICS = ['calls', 'answered', 'abandoned'];

    private const DURATION_METRICS = ['avg_waiting', 'avg_talking'];

    /**
     * Views that make sense for the range: day if more than one day; week if more than 7 days
     * over at least two ISO weeks; month if 28+ days over at least two calendar months.
     *
     * @return list<string>
     */
    public static function available(?string $startDate, ?string $endDate): array
    {
        if (! $startDate || ! $endDate) {
            return [self::TOTAL];
        }

        try {
            $start = CarbonImmutable::parse($startDate)->startOfDay();
            $end = CarbonImmutable::parse($endDate)->startOfDay();
        } catch (\Throwable) {
            return [self::TOTAL];
        }

        if ($end->lessThan($start)) {
            return [self::TOTAL];
        }

        $days = (int) $start->diffInDays($end) + 1;

        $available = [self::TOTAL];
        if ($days > 1) {
            $available[] = self::DAY;
        }
        if ($days > 7 && ! $start->startOfWeek(CarbonImmutable::MONDAY)->equalTo($end->startOfWeek(CarbonImmutable::MONDAY))) {
            $available[] = self::WEEK;
        }
        if ($days >= 28 && $start->format('Y-m') !== $end->format('Y-m')) {
            $available[] = self::MONTH;
        }

        return $available;
    }

    public static function isBucketed(string $period): bool
    {
        return \in_array($period, self::BUCKETED, true);
    }

    /** Accepts "Y-m-d" (current API) and the legacy "Ymd"; null for anything else. */
    public static function normalizeDate(?string $day): ?string
    {
        if ($day === null) {
            return null;
        }
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $day, $m)) {
            return "{$m[1]}-{$m[2]}-{$m[3]}";
        }
        if (preg_match('/^(\d{4})(\d{2})(\d{2})$/', $day, $m)) {
            return "{$m[1]}-{$m[2]}-{$m[3]}";
        }

        return null;
    }

    /** Localised full date: dd/mm/yyyy (fr) or mm/dd/yyyy (en). Unknown formats are returned as is. */
    public static function formatDay(string $day): string
    {
        $date = self::normalizeDate($day);

        return $date === null ? $day : CarbonImmutable::parse($date)->format(self::t('date_format_day'));
    }

    /** Localised short date (dd/mm or mm/dd). */
    public static function formatShortDay(string $day): string
    {
        $date = self::normalizeDate($day);

        return $date === null ? $day : CarbonImmutable::parse($date)->format(self::t('date_format_short'));
    }

    /**
     * Human label of a period: "janvier 2026" / "January 2026", "S10 · 02/03 – 08/03" /
     * "W10 · 03/02 – 03/08" (clipped dates), or the localised day.
     *
     * @param  string  $key  `2026-03`, `2026-W10` or `2026-03-05`
     */
    public static function label(string $period, string $key, ?string $start = null, ?string $end = null): string
    {
        return match ($period) {
            self::MONTH => self::monthLabel($key),
            self::WEEK => self::weekLabel($key, $start, $end),
            self::DAY => self::formatDay($start ?? $key),
            default => $key,
        };
    }

    /**
     * Labels of the API's `periods` list, by key.
     *
     * @param  array<int, array<string, mixed>>  $periods
     * @return array<string, string>
     */
    public static function labels(string $period, array $periods): array
    {
        $labels = [];
        foreach ($periods as $p) {
            $key = (string) ($p['key'] ?? '');
            $labels[$key] = self::label($period, $key, $p['start'] ?? null, $p['end'] ?? null);
        }

        return $labels;
    }

    /** Label of a report row of a bucketed view (rows carry period, period_start, period_end). */
    public static function rowLabel(string $period, array $row): string
    {
        return self::label($period, (string) ($row['period'] ?? $row['day'] ?? ''), $row['period_start'] ?? null, $row['period_end'] ?? null);
    }

    /** Tooltip of a clipped period: "Partial period: 15/01 – 31/01". */
    public static function partialTooltip(?string $start, ?string $end): string
    {
        return self::t('partial_tooltip', [
            'start' => self::formatShortDay((string) $start),
            'end' => self::formatShortDay((string) $end),
        ]);
    }

    /**
     * Splits ordered report rows into consecutive period groups (order of first appearance),
     * each with its label and dates.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return list<array{key: string, label: string, start: ?string, end: ?string, partial: bool, rows: list<array<string, mixed>>}>
     */
    public static function groupByPeriod(string $period, array $rows): array
    {
        $groups = [];
        foreach ($rows as $row) {
            $key = (string) ($row['period'] ?? $row['day'] ?? '');
            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'key' => $key,
                    'label' => self::rowLabel($period, $row),
                    'start' => $row['period_start'] ?? self::normalizeDate($row['day'] ?? null),
                    'end' => $row['period_end'] ?? self::normalizeDate($row['day'] ?? null),
                    'partial' => (bool) ($row['partial'] ?? false),
                    'rows' => [],
                ];
            }
            $groups[$key]['rows'][] = $row;
        }

        return array_values($groups);
    }

    /** Lookup key shared by report rows and comparison entries. */
    public static function seriesKey(array $row): string
    {
        return ($row['period'] ?? '').'|'.($row['queue'] ?? '').'|'.($row['element'] ?? '');
    }

    /**
     * Comparison entries indexed by period|queue|element, keeping only the badge metrics and
     * the fields the badges need (keeps the Livewire payload small).
     *
     * @param  array<int, array<string, mixed>>  $comparison
     * @return array<string, array<string, array<string, mixed>>>
     */
    public static function indexComparison(array $comparison): array
    {
        $keep = ['current', 'previous', 'delta', 'delta_percent', 'normalized', 'trend', 'assessment', 'severity'];
        $index = [];
        foreach ($comparison as $entry) {
            $metrics = [];
            foreach (self::BADGE_METRICS as $metric) {
                if (isset($entry['metrics'][$metric]) && \is_array($entry['metrics'][$metric])) {
                    $metrics[$metric] = array_intersect_key($entry['metrics'][$metric], array_flip($keep));
                    $metrics[$metric]['previous_period'] = $entry['previous_period'] ?? null;
                }
            }
            $index[self::seriesKey($entry)] = $metrics;
        }

        return $index;
    }

    /**
     * Anomalies to show: queue rows only in the queues-only view.
     *
     * @param  array<int, array<string, mixed>>  $anomalies
     * @return list<array<string, mixed>>
     */
    public static function visibleAnomalies(array $anomalies, bool $queuesOnly): array
    {
        return array_values(array_filter($anomalies, fn (array $a): bool => ! $queuesOnly || ($a['keyType'] ?? null) == 4));
    }

    /**
     * Compact delta badge of a compared metric, or null when there is nothing to compare
     * (first period, value missing on one side).
     *
     * @param  array<string, mixed>|null  $m  one metric of a comparison entry
     * @param  array<string, string>  $labels  period labels by key (clipped dates), see labels()
     * @return array{text: string, class: string, title: string, severity: ?string, assessment: string}|null
     */
    public static function badge(string $metric, ?array $m, string $period = self::MONTH, array $labels = []): ?array
    {
        if ($m === null || ! isset($m['delta']) || $m['delta'] === null) {
            return null;
        }

        $trend = (string) ($m['trend'] ?? 'flat');
        $arrow = match (true) {
            $trend === 'up', $trend === 'new' => '▲',
            $trend === 'down', $trend === 'gone' => '▼',
            default => '=',
        };

        $text = trim($arrow.' '.self::formatDelta($metric, $m));
        $assessment = (string) ($m['assessment'] ?? 'neutral');
        $severity = $m['severity'] ?? null;

        $title = self::t('badge_previous', [
            'period' => isset($m['previous_period'])
                ? ($labels[$m['previous_period']] ?? self::label($period, (string) $m['previous_period']))
                : '',
            'value' => self::formatValue($metric, $m['previous'] ?? null),
        ]);
        if (! empty($m['normalized'])) {
            $title .= ' · '.self::t('badge_normalized');
        }
        if ($severity !== null) {
            $title .= ' · '.self::t('severity_'.$severity);
        }

        return [
            'text' => $text,
            'class' => self::badgeClass($assessment, $severity),
            'title' => $title,
            'severity' => $severity,
            'assessment' => $assessment,
        ];
    }

    /**
     * "+12 %" for volumes (per-day % when normalized; absolute when the previous value was 0),
     * "+3.2 pts" for the answer rate, "+8 s" for durations.
     */
    public static function formatDelta(string $metric, array $m): string
    {
        $delta = $m['delta'] ?? null;
        $percent = $m['delta_percent'] ?? null;

        if (\in_array($metric, self::VOLUME_METRICS, true)) {
            return $percent !== null
                ? self::signed((float) $percent, 0).' %'
                : self::signed((float) $delta, 0);
        }

        if ($metric === 'answer_rate') {
            return self::signed((float) $delta, 1).' '.self::t('unit_points');
        }

        if (\in_array($metric, self::DURATION_METRICS, true)) {
            return self::signed((float) $delta, 0).' s';
        }

        return self::signed((float) $delta, 1);
    }

    /** Value of a metric for display (tooltips, anomalies panel). */
    public static function formatValue(string $metric, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if ($metric === 'answer_rate') {
            return number_format((float) $value, 1).' %';
        }

        if (\in_array($metric, self::DURATION_METRICS, true)) {
            return self::formatSeconds((float) $value);
        }

        return (string) (int) round((float) $value);
    }

    /** Badge classes: colour from the assessment, weight from the severity. */
    public static function badgeClass(string $assessment, ?string $severity): string
    {
        if ($severity === null) {
            return 'text-gray-400 dark:text-gray-500';
        }

        $colour = match ($assessment) {
            'better' => $severity === 'major'
                ? 'bg-green-100 text-green-800 ring-1 ring-green-500 dark:bg-green-950/60 dark:text-green-300 dark:ring-green-600'
                : 'bg-green-50 text-green-700 dark:bg-green-950/30 dark:text-green-400',
            'worse' => $severity === 'major'
                ? 'bg-red-100 text-red-800 ring-1 ring-red-500 dark:bg-red-950/60 dark:text-red-300 dark:ring-red-600'
                : 'bg-red-50 text-red-700 dark:bg-red-950/30 dark:text-red-400',
            default => $severity === 'major'
                ? 'bg-amber-100 text-amber-800 ring-1 ring-amber-500 dark:bg-amber-950/60 dark:text-amber-300 dark:ring-amber-600'
                : 'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400',
        };

        return 'rounded px-1 '.$colour.($severity === 'major' ? ' font-semibold' : '');
    }

    /** Severity pill classes (anomalies panel). */
    public static function severityClass(?string $severity): string
    {
        return $severity === 'major'
            ? 'bg-red-600 text-white dark:bg-red-500'
            : 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300';
    }

    /** Text colour of an anomaly's change, from its assessment. */
    public static function assessmentTextClass(string $assessment): string
    {
        return match ($assessment) {
            'better' => 'text-green-700 dark:text-green-400',
            'worse' => 'text-red-700 dark:text-red-400',
            default => 'text-amber-700 dark:text-amber-400',
        };
    }

    public static function metricLabel(string $metric): string
    {
        return self::t('metric_'.$metric);
    }

    private static function monthLabel(string $key): string
    {
        if (! preg_match('/^(\d{4})-(\d{2})$/', $key, $m)) {
            return $key;
        }

        $label = CarbonImmutable::create((int) $m[1], (int) $m[2], 1)->locale(app()->getLocale())->isoFormat('MMMM YYYY');

        return mb_strtoupper(mb_substr($label, 0, 1)).mb_substr($label, 1);
    }

    private static function weekLabel(string $key, ?string $start, ?string $end): string
    {
        if (! preg_match('/^(\d{4})-W(\d{1,2})$/', $key, $m)) {
            return $key;
        }

        if ($start === null || $end === null) {
            $monday = CarbonImmutable::now()->setISODate((int) $m[1], (int) $m[2])->startOfDay();
            $start ??= $monday->toDateString();
            $end ??= $monday->addDays(6)->toDateString();
        }

        return self::t('week_label', [
            'week' => (int) $m[2],
            'start' => self::formatShortDay($start),
            'end' => self::formatShortDay($end),
        ]);
    }

    private static function signed(float $value, int $decimals): string
    {
        $rounded = round($value, $decimals);
        $formatted = number_format(abs($rounded), $decimals, '.', '');

        return ($rounded > 0 ? '+' : ($rounded < 0 ? '−' : '±')).$formatted;
    }

    private static function formatSeconds(float $seconds): string
    {
        $secs = (int) round($seconds);
        if ($secs < 60) {
            return $secs.' s';
        }

        return intdiv($secs, 60).' min '.str_pad((string) ($secs % 60), 2, '0', STR_PAD_LEFT).' s';
    }

    private static function t(string $key, array $replace = []): string
    {
        return (string) __('expert-statistics::pbx.queue_report_periods.'.$key, $replace);
    }
}
