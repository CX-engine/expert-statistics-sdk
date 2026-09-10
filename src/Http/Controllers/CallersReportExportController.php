<?php

namespace CXEngine\ExpertStatistics\Http\Controllers;

use CXEngine\ExpertStatistics\Services\ExpertStatisticsService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams the Caller Numbers report Excel export, ported from
 * bluerocktelclients' App\Http\Controllers\ExpertStatistics\CallersReportExportController.
 *
 * Proxies ExpertStatisticsService::streamCallerReportFile()'s raw Saloon
 * Response body straight through as a file download - that method must NOT
 * be called with ->json(), only ->body()/->stream(), since the backend
 * returns a binary .xlsx payload.
 *
 * This controller has no permission/auth check of its own: it is expected
 * to be registered (by the host app, in a later phase) inside the same
 * authenticated route group as the package's 11 Livewire report pages,
 * mirroring the source controller's own minimal gating style.
 */
class CallersReportExportController extends Controller
{
    public function __invoke(Request $request, ExpertStatisticsService $service): StreamedResponse
    {
        $params = array_filter($request->only([
            'start_date',
            'end_date',
            'start_time',
            'end_time',
            'dn',
            'group_names',
            'all_groups',
            'groupless',
            'queues',
            'exclude_closed_hours',
        ]));

        $response = $service->streamCallerReportFile($params);

        $startDate = $request->query('start_date', 'unknown');
        $endDate = $request->query('end_date', 'unknown');
        $filename = "Rapport_Numeros_{$startDate}_au_{$endDate}.xlsx";

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
