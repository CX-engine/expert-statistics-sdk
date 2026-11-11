<?php

namespace CXEngine\ExpertStatistics\Support;

use Illuminate\Support\Facades\Route;

/**
 * Builds drill-down links from report/dashboard stats to the call analysis
 * page, pre-filtered (via its #[Url] properties) on the calls behind a stat.
 * The route is registered by the host app, so url() returns null when it
 * isn't - views then render the stat as plain text.
 */
class CallAnalysisLink
{
    public const ROUTE = 'expert-stats.call-details.index';

    /**
     * Base params pinning the call analysis page to an explicit period.
     */
    public static function period(?string $startDate, ?string $endDate, ?string $startTime, ?string $endTime): array
    {
        return [
            'selectedPeriod' => 'custom',
            'startDate' => $startDate, 'endDate' => $endDate,
            'startTime' => $startTime, 'endTime' => $endTime,
        ];
    }

    /**
     * Converts a report row's day key ("Y-m-d", or the legacy "Ymd") to "Y-m-d", or null
     * when it isn't one.
     */
    public static function dayToDate(?string $day): ?string
    {
        return QueueReportPeriods::normalizeDate($day);
    }

    /**
     * [start, end] ("Y-m-d") of a report row: its period (week/month views carry
     * period_start/period_end), its day, or null when the row covers the whole range.
     *
     * @return array{0: string, 1: string}|null
     */
    public static function rowRange(array $row): ?array
    {
        $start = self::dayToDate($row['period_start'] ?? null);
        $end = self::dayToDate($row['period_end'] ?? null);
        if ($start !== null && $end !== null) {
            return [$start, $end];
        }

        $day = self::dayToDate(isset($row['day']) ? (string) $row['day'] : null);

        return $day !== null ? [$day, $day] : null;
    }

    public static function url(array $params): ?string
    {
        if (! Route::has(self::ROUTE)) {
            return null;
        }

        return route(self::ROUTE, array_filter($params, fn ($v) => $v !== '' && $v !== null));
    }
}
