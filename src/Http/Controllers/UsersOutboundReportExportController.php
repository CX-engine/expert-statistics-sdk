<?php

namespace CXEngine\ExpertStatistics\Http\Controllers;

use CXEngine\ExpertStatistics\Services\ExpertStatisticsService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams the My Users outbound calls report Excel export
 * (CXEngine\ExpertStatistics\Livewire\Reports\MyUsers\MyUsersOutbound).
 *
 * Same contract as UsersReportExportController: the backend returns a
 * binary .xlsx payload, proxied straight through, and the host app
 * registers this controller inside its authenticated report route group.
 */
class UsersOutboundReportExportController extends Controller
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

        $response = $service->streamUserOutboundReportFile($params);

        $startDate = $request->query('start_date', 'unknown');
        $endDate = $request->query('end_date', 'unknown');
        $filename = "Rapport_Appels_Sortants_Utilisateurs_{$startDate}_au_{$endDate}.xlsx";

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
