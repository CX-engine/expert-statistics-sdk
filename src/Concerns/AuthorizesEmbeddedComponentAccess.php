<?php

namespace CXEngine\ExpertStatistics\Concerns;

/**
 * Gates a Livewire component that is not a page of its own but is embedded in
 * several of them (ShareReportModal, DocsHelperPanel, DocsAssistantChat). It
 * sends its own requests, which never re-run the host page's check, so a tab
 * left open after access is revoked could otherwise keep calling it.
 *
 * Since these components also sit on the Dashboard and on the settings and
 * scheduled reports pages, whose permission lists a host app may set apart
 * from the module-wide one, a user passes if they may open any of those pages.
 * Refusing them here would 403 the very page they were let into.
 *
 * Livewire calls `boot{TraitName}()` automatically on every request.
 */
trait AuthorizesEmbeddedComponentAccess
{
    use AuthorizesExpertStatisticsAccess;

    /**
     * @return array<int, string>
     */
    protected function expertStatisticsAccessPermissions(): array
    {
        return array_values(array_unique([
            ...(array) config('expert-statistics-api.permissions', []),
            ...(array) config('expert-statistics-api.dashboard_permissions', []),
            ...(array) config('expert-statistics-api.configuration_permissions', []),
            ...(array) config('expert-statistics-api.scheduled_reports_permissions', []),
        ]));
    }
}
