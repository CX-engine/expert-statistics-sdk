<?php

namespace CXEngine\ExpertStatistics\Http\Controllers;

use CXEngine\ExpertStatistics\Services\ExpertStatisticsService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams the My Numbers (DID) report Excel export.
 *
 * Unlike the other three export controllers in this directory, there is no
 * bluerocktelclients controller to port this from - bluerocktelclients never
 * wired a UI export button for the DID report, even though the underlying
 * `did-report-file` endpoint exists on the backend and is wrapped by
 * ExpertStatisticsService::streamDidReportFile() (see the SDK's
 * DidReportFileRequest). This controller is built directly from that SDK/
 * service method, following the same shape as its three siblings here.
 *
 * Proxies ExpertStatisticsService::streamDidReportFile()'s raw Saloon
 * Response body straight through as a file download - that method must NOT
 * be called with ->json(), only ->body()/->stream(), since the backend
 * returns a binary .xlsx payload.
 *
 * This controller has no permission/auth check of its own: it is expected
 * to be registered (by the host app, in a later phase) inside the same
 * authenticated route group as the package's 11 Livewire report pages,
 * mirroring the sibling export controllers' own minimal gating style.
 */
class DidReportExportController extends Controller
{
    public function __invoke(Request $request, ExpertStatisticsService $service): StreamedResponse
    {
        $params = array_filter($request->only([
            'start_date',
            'end_date',
            'start_time',
            'end_time',
            'dn',
            'exclude_closed_hours',
        ]));

        $response = $service->streamDidReportFile($params);

        $startDate = $request->query('start_date', 'unknown');
        $endDate = $request->query('end_date', 'unknown');
        $filename = "Rapport_SDA_{$startDate}_au_{$endDate}.xlsx";

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
