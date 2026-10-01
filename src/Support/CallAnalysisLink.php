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
     * Converts a report row's "Ymd" day key to "Y-m-d", or null when it isn't one.
     */
    public static function dayToDate(?string $day): ?string
    {
        return $day !== null && strlen($day) === 8
            ? substr($day, 0, 4).'-'.substr($day, 4, 2).'-'.substr($day, 6, 2)
            : null;
    }

    public static function url(array $params): ?string
    {
        if (! Route::has(self::ROUTE)) {
            return null;
        }

        return route(self::ROUTE, array_filter($params, fn ($v) => $v !== '' && $v !== null));
    }
}
