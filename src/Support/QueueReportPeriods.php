<?php

namespace CXEngine\ExpertStatistics\Support;

use Carbon\CarbonImmutable;

/**
 * Pure shaping of the My Queues report's period views (total / day / week / month) and of
 * the anomaly detection returned by `queues-report-aggregated` when it gets a `period`
 * parameter (expert-stats QueueReportPeriodBreakdown / QueueReportPeriodComparison): each
 * period against the usual level of its queue / extension over the other periods of the
 * range (same weekday in the day view).
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

    /** Indicators the API judges (better / worse than usual), marked in the table when abnormal. */
    public const JUDGED_METRICS = ['answer_rate', 'avg_waiting', 'abandoned'];

    private const DURATION_METRICS = ['avg_waiting', 'avg_talking'];

    /** Alerts shown before "Show all" in the points of attention panel. */
    public const ATTENTION_LIMIT = 5;

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

    /**
     * Average talk time per answered call (seconds), null without answered calls.
     */
    public static function averageTalking(array $row): ?float
    {
        $answered = (float) ($row['answered'] ?? 0);

        return $answered > 0 ? round((float) ($row['talking_duration'] ?? 0) / $answered, 1) : null;
    }

    /**
     * Average wait per call that waited, pre-recorded message excluded (seconds) — the API's
     * `net_waiting_duration / waiting_calls` (same formula as its comparison and export).
     * waiting_duration / answered would be wrong: the waiting calls of a queue include calls
     * answered by another queue (overflow), and waiting_duration includes the message.
     * Payloads without these fields (older API) fall back to (waiting − message) / answered.
     */
    public static function averageWaiting(array $row): ?float
    {
        if (\array_key_exists('waiting_calls', $row)) {
            $calls = (float) $row['waiting_calls'];

            return $calls > 0 ? round((float) ($row['net_waiting_duration'] ?? 0) / $calls, 1) : null;
        }

        $answered = (float) ($row['answered'] ?? 0);

        return $answered > 0
            ? round(max(0.0, (float) ($row['waiting_duration'] ?? 0) - (float) ($row['preanswer_duration'] ?? 0)) / $answered, 1)
            : null;
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
     * Comparison entries indexed by period|queue|element, keeping only the judged metrics and
     * the fields the table needs (keeps the Livewire payload small).
     *
     * @param  array<int, array<string, mixed>>  $comparison
     * @return array<string, array<string, array<string, mixed>>>
     */
    public static function indexComparison(array $comparison): array
    {
        $keep = ['current', 'usual', 'assessment', 'severity'];
        $index = [];
        foreach ($comparison as $entry) {
            $metrics = [];
            foreach (self::JUDGED_METRICS as $metric) {
                if (isset($entry['metrics'][$metric]) && \is_array($entry['metrics'][$metric])) {
                    $metrics[$metric] = array_intersect_key($entry['metrics'][$metric], array_flip($keep));
                }
            }
            $index[self::seriesKey($entry)] = $metrics;
        }

        return $index;
    }

    /**
     * Alerts to show: queue rows only in the queues-only view. The API sends them queues
     * first, most severe first.
     *
     * @param  array<int, array<string, mixed>>  $anomalies
     * @return list<array<string, mixed>>
     */
    public static function visibleAnomalies(array $anomalies, bool $queuesOnly): array
    {
        return array_values(array_filter($anomalies, fn (array $a): bool => ! empty($a['issues']) && (! $queuesOnly || ($a['keyType'] ?? null) == 4)));
    }

    /**
     * Marker under an abnormal value of the table: "▼ habit. 88.0 %" in red (worse than
     * usual) or green (better), or null when the value is in the usual range.
     *
     * @param  array<string, mixed>|null  $m  one judged metric of a comparison entry
     * @return array{text: string, class: string, title: string}|null
     */
    public static function usualMarker(string $metric, ?array $m): ?array
    {
        if ($m === null || ($m['severity'] ?? null) === null || ! \in_array($m['assessment'] ?? null, ['better', 'worse'], true)) {
            return null;
        }

        $worse = $m['assessment'] === 'worse';
        $up = (float) ($m['current'] ?? 0) > (float) ($m['usual'] ?? 0);
        $usual = self::formatValue($metric, $m['usual'] ?? null);
        $major = $m['severity'] === 'major';

        return [
            'text' => ($up ? '▲' : '▼').' '.self::t('usual_short', ['value' => $usual]),
            'class' => ($worse ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400').($major ? ' font-semibold' : ''),
            'title' => self::t($worse ? 'usual_title_worse' : 'usual_title_better', ['metric' => self::metricLabel($metric), 'value' => $usual]),
        ];
    }

    /**
     * Sentence parts of an alert: "Taux de réponse 72.0 % (habituellement 88.0 %)" per worse
     * indicator, and the call volume as context ("35 % d'appels de plus que d'habitude").
     *
     * @param  array<string, mixed>  $anomaly
     * @return array{issues: list<string>, context: ?string}
     */
    public static function anomalySummary(array $anomaly): array
    {
        $issues = [];
        foreach ($anomaly['issues'] ?? [] as $metric => $m) {
            $issues[] = self::t('attention_issue', [
                'metric' => self::metricLabel((string) $metric),
                'value' => self::formatValue((string) $metric, $m['current'] ?? null),
                'usual' => self::formatValue((string) $metric, $m['usual'] ?? null),
            ]);
        }

        $context = null;
        $calls = $anomaly['context']['calls'] ?? null;
        if (\is_array($calls) && isset($calls['delta_percent'])) {
            $percent = (int) round(abs((float) $calls['delta_percent']));
            $context = self::t((float) $calls['delta_percent'] > 0 ? 'attention_calls_more' : 'attention_calls_less', ['percent' => $percent]);
        }

        return ['issues' => $issues, 'context' => $context];
    }

    /** Value of a metric for display (markers, alerts). */
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

    /** Severity pill classes (points of attention). */
    public static function severityClass(?string $severity): string
    {
        return $severity === 'major'
            ? 'bg-red-600 text-white dark:bg-red-500'
            : 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300';
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
