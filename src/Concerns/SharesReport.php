<?php

namespace CXEngine\ExpertStatistics\Concerns;

/**
 * Send/schedule toolbar actions (<x-expert-statistics::report-action-buttons />)
 * for a page whose report isn't the default one of its $urlType - dashboards,
 * KPI and origins pages - opening
 * CXEngine\ExpertStatistics\Livewire\Reports\ShareReportModal with that
 * page's report type, as bluerocktelclients' pages did before this package.
 */
trait SharesReport
{
    /**
     * The API report_type this page's reports are stored under
     * ('dashboard', 'answered', 'origins'...).
     */
    abstract protected function sharedReportType(): string;

    /** Opens ShareReportModal in "send" mode. */
    public function shareReport(): void
    {
        $this->openReportModal('open-share-report');
    }

    /** Opens ShareReportModal in "schedule" mode. */
    public function scheduleReport(): void
    {
        $this->openReportModal('open-schedule-report');
    }

    private function openReportModal(string $event): void
    {
        $this->dispatch($event,
            urlType: $this->urlType,
            startDate: $this->startDate,
            endDate: $this->endDate,
            startTime: $this->startTime,
            endTime: $this->endTime,
            elements: implode(',', $this->selectedElements),
            reportType: $this->sharedReportType(),
        );
    }
}
