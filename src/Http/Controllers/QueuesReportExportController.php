<?php

namespace CXEngine\ExpertStatistics\Http\Controllers;

use CXEngine\ExpertStatistics\Services\ExpertStatisticsService;
use CXEngine\ExpertStatistics\Support\QueueReportPeriods;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams the My Queues report Excel export, ported from bluerocktelclients'
 * App\Http\Controllers\ExpertStatistics\QueuesReportExportController.
 *
 * Proxies ExpertStatisticsService::streamQueueReportFile()'s raw Saloon
 * Response body straight through as a file download - that method must NOT
 * be called with ->json(), only ->body()/->stream(), since the backend
 * returns a binary .xlsx payload.
 *
 * This controller has no permission/auth check of its own: it is expected
 * to be registered (by the host app, in a later phase) inside the same
 * authenticated route group as the package's 11 Livewire report pages,
 * mirroring the source controller's own minimal gating style.
 */
class QueuesReportExportController extends Controller
{
    public function __invoke(Request $request, ExpertStatisticsService $service): StreamedResponse
    {
        // Only null/'' are dropped: compare=0 must reach the API (its default is true).
        $params = array_filter($request->only([
            'start_date',
            'end_date',
            'start_time',
            'end_time',
            'dn',
            'exclude_closed_hours',
            'period',
            'compare',
        ]), fn ($v) => $v !== null && $v !== '');

        // `period` adds the per-day/week/month and comparison sheets; total is the default file.
        if (! \in_array($params['period'] ?? null, QueueReportPeriods::BUCKETED, true)) {
            unset($params['period'], $params['compare']);
        }

        $response = $service->streamQueueReportFile($params);

        $startDate = $request->query('start_date', 'unknown');
        $endDate = $request->query('end_date', 'unknown');
        $filename = "Rapport_Files_{$startDate}_au_{$endDate}.xlsx";

        $body = $response->body();

        return response()->streamDownload(
            fn () => print ($body),
            $filename,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="'.addslashes($filename).'"',
            ],
        );
    }
}
