{{--
    Share/Schedule toolbar buttons, ported from bluerocktelclients'
    resources/views/components/{dashboard,expert-statistics}/action-buttons.blade.php
    (merged into one component — the "view reports" link isn't included since this
    package doesn't own a route to the scheduled-reports page; the host app can link
    to CXEngine\ExpertStatistics\Livewire\Reports\ManageScheduledReports itself).

    Expects the host Livewire component to define shareReport()/scheduleReport()
    methods that dispatch the 'open-share-report'/'open-schedule-report' events
    CXEngine\ExpertStatistics\Livewire\Reports\ShareReportModal listens for.
--}}
<div class="inline-flex items-center rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 divide-x divide-gray-200 dark:divide-gray-700 overflow-hidden shrink-0">
    <button
        wire:click="shareReport"
        type="button"
        title="{{ __('expert-statistics::pbx.dashboards.sendReport') }}"
        class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-white dark:hover:bg-gray-700 hover:text-gray-800 dark:hover:text-gray-100 transition"
    >
        <x-heroicon-o-share class="w-4 h-4 shrink-0" />
        <span class="hidden sm:inline">{{ __('expert-statistics::pbx.dashboards.send') }}</span>
    </button>
    <button
        wire:click="scheduleReport"
        type="button"
        title="{{ __('expert-statistics::pbx.dashboards.scheduleReport') }}"
        class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-white dark:hover:bg-gray-700 hover:text-gray-800 dark:hover:text-gray-100 transition"
    >
        <x-heroicon-o-calendar-days class="w-4 h-4 shrink-0" />
        <span class="hidden sm:inline">{{ __('expert-statistics::pbx.dashboards.schedule') }}</span>
    </button>
</div>
