<?php

namespace CXEngine\ExpertStatistics\Http\Controllers;

use CXEngine\ExpertStatistics\Services\ExpertStatisticsService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams the CDR ("Call Flow Analysis") Excel export, ported from
 * bluerocktelclients' App\Http\Controllers\ExpertStatistics\CdrExportController.
 *
 * Proxies ExpertStatisticsService::streamCdrExport()'s raw Saloon Response
 * body straight through as a file download - that method must NOT be called
 * with ->json(), only ->body()/->stream(), since the backend returns a
 * binary .xlsx payload.
 *
 * This controller has no permission/auth check of its own: it is expected
 * to be registered (by the host app, in a later phase) inside the same
 * authenticated route group as the package's 11 Livewire report pages,
 * mirroring the source controller's own minimal gating style.
 */
class CdrExportController extends Controller
{
    public function __invoke(Request $request, ExpertStatisticsService $service): StreamedResponse
    {
        $params = array_filter($request->only([
            'start_date',
            'end_date',
            'start_time',
            'end_time',
            'origin_dn',
            'origin_dn_type',
            'destination_dn',
            'destination_dn_type',
            'did_number',
            'caller_number',
            'call_way',
            'call_status',
            'exclude_closed_hours',
        ]));

        $response = $service->streamCdrExport($params);

        $startDate = $request->query('start_date', 'unknown');
        $endDate = $request->query('end_date', 'unknown');
        $filename = "CDR_Report_{$startDate}_to_{$endDate}.xlsx";

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
