@use('CXEngine\ExpertStatistics\Support\CallAnalysisLink')
@use('CXEngine\ExpertStatistics\Support\QueueReportPeriods')
{{-- Tailwind source: classes emitted by Support\QueueReportPeriods (src/ is not scanned by the host build):
    rounded px-1 font-semibold ring-1 text-gray-400 dark:text-gray-500 text-white
    bg-green-50 bg-green-100 text-green-700 text-green-800 ring-green-500 dark:bg-green-950/30 dark:bg-green-950/60 dark:text-green-300 dark:text-green-400 dark:ring-green-600
    bg-red-50 bg-red-100 bg-red-600 text-red-700 text-red-800 ring-red-500 dark:bg-red-500 dark:bg-red-950/30 dark:bg-red-950/60 dark:text-red-300 dark:text-red-400 dark:ring-red-600
    bg-amber-50 bg-amber-100 text-amber-700 text-amber-800 ring-amber-500 dark:bg-amber-900/50 dark:bg-amber-950/30 dark:bg-amber-950/60 dark:text-amber-300 dark:text-amber-400 dark:ring-amber-600 --}}
@php
    $tp = fn (string $key, array $r = []) => __('expert-statistics::pbx.queue_report_periods.'.$key, $r);
@endphp
<x-expert-statistics::cluster-layout :title="__('expert-statistics::pbx.expert_statistics.my_queues_title')">

<div class="space-y-5">

    <div class="flex justify-between items-center">
        {{-- Element selector --}}
        <x-expert-statistics::element-selector :urlType="$urlType" :pbxElements="$pbxElements"
            :selectedElements="$selectedElements" />

        <div class="flex items-center gap-2">
            <x-expert-statistics::report-action-buttons />
            <x-expert-statistics::export-button :url="$this->getExportUrl()" />
        </div>
    </div>

    {{-- Filter bar --}}
    <x-expert-statistics::filter-bar :selectedPeriod="$selectedPeriod" :startDate="$startDate" :endDate="$endDate"
        :startTime="$startTime" :endTime="$endTime" :showTimeRange="true" />

    @if ($errorMessage)
        <div class="rounded-xl bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-900 px-4 py-3 text-sm text-red-700 dark:text-red-400">
            {{ $errorMessage }}
        </div>
    @endif

    {{-- Toggles row --}}
    @if (! empty($queueData) || ! empty($extensionData))
        <div class="flex flex-wrap gap-3 items-center justify-between">
            <div class="flex flex-wrap gap-3 items-center">

                {{-- Show extensions toggle --}}
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" wire:model.live="queuesOnlyStats"
                        class="rounded border-gray-300 dark:border-gray-600 text-primary-600" />
                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('expert-statistics::pbx.expert_statistics.my_queues_queues_only') }}</span>
                </label>

                {{-- Exclude closed hours toggle --}}
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" wire:model.live="excludeClosedHours"
                        class="rounded border-gray-300 dark:border-gray-600 text-primary-600" />
                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('expert-statistics::pbx.expert_statistics.exclude_closed_hours') }}</span>
                </label>

                {{-- Unique calls toggle --}}
                <x-expert-statistics::unique-calls-toggle
                    :enabled="$consolidateData"
                    :checked="$uniqueCalls"
                    wireModel="uniqueCalls"
                />

                {{-- Consolidate toggle (queues only) --}}
                @if ($queuesOnlyStats)
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" wire:model.live="consolidateData"
                            class="rounded border-gray-300 dark:border-gray-600 text-primary-600" />
                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('expert-statistics::pbx.expert_statistics.toggle_consolidate') }}</span>
                    </label>
                @endif

            </div>

            <div class="flex flex-wrap items-center gap-3">
                {{-- Compare with the previous period (day / week / month) --}}
                @if ($this->isBucketed())
                    <label class="inline-flex items-center gap-2 cursor-pointer" title="{{ $tp('compare_hint') }}">
                        <input type="checkbox" wire:model.live="compare"
                            class="rounded border-gray-300 dark:border-gray-600 text-primary-600" />
                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ $tp('compare_toggle') }}</span>
                    </label>
                @endif

                {{-- Total / day / week / month --}}
                <div class="flex bg-gray-100 dark:bg-gray-800 rounded-xl p-1 gap-1" role="group" aria-label="{{ $tp('view_label') }}">
                    @foreach (QueueReportPeriods::ALL as $view)
                        @php $enabled = in_array($view, $availableBreakdowns, true); @endphp
                        <button wire:click="selectBreakdown('{{ $view }}')" type="button"
                            @disabled(! $enabled)
                            @if (! $enabled) title="{{ $tp('view_unavailable') }}" @endif
                            aria-pressed="{{ $breakdown === $view ? 'true' : 'false' }}"
                            class="px-3 py-2 text-sm font-medium rounded-lg transition-all
                                {{ $breakdown === $view
                                    ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm'
                                    : ($enabled
                                        ? 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'
                                        : 'text-gray-300 dark:text-gray-600 cursor-not-allowed') }}">
                            {{ $tp('view_'.$view) }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    @php
        $loadingTargets = 'applyFilters,selectPeriod,toggleGroupData,selectBreakdown,compare,setCustomRange,updateTimeRange,applyPbxElementSelection,selectAllPbxElements,clearPbxElements,removePbxElement';
    @endphp

    {{-- Loading --}}
    <div wire:loading.flex wire:target="{{ $loadingTargets }}" class="items-center justify-center py-10">
        <svg class="w-8 h-8 text-primary-500 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
        </svg>
    </div>

    {{-- Empty state --}}
    @if (! $dn)
        <div wire:loading.remove wire:target="{{ $loadingTargets }}"
            class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-12 text-center">
            <x-heroicon-o-queue-list class="w-12 h-12 mx-auto text-gray-200 dark:text-gray-700 mb-4" />
            <h3 class="text-base font-semibold text-gray-600 dark:text-gray-400 mb-2">{{ __('expert-statistics::pbx.expert_statistics.my_queues_title') }}</h3>
            <p class="text-sm text-gray-400 dark:text-gray-500">{{ __('expert-statistics::pbx.expert_statistics.my_queues_empty') }}</p>
        </div>

    @elseif (empty($queueData) && empty($extensionData))
        <div wire:loading.remove wire:target="{{ $loadingTargets }}"
            class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-10 text-center">
            <x-heroicon-o-chart-bar class="w-10 h-10 mx-auto text-gray-200 dark:text-gray-700 mb-3" />
            <p class="text-sm text-gray-400 dark:text-gray-500">{{ __('expert-statistics::pbx.expert_statistics.my_queues_no_data') }}</p>
        </div>

    @else
        @php
            $rows = $this->getDisplayData();
            $bucketed = $this->isBucketed();
            $periodLabels = $this->getPeriodLabels();
            $groups = $this->groupsRowsByPeriod()
                ? QueueReportPeriods::groupByPeriod($breakdown, $rows)
                : [['key' => null, 'rows' => $rows]];
            $colCount = 9 + ($queuesOnlyStats ? 0 : 1) + ($bucketed ? 1 : 0);
            $visibleAnomalies = $this->getVisibleAnomalies();

            /** Small delta badge under a value (comparison on). */
            $badge = function (array $row, string $metric) use ($periodLabels): string {
                $b = $this->comparisonBadge($row, $metric, $periodLabels);
                if ($b === null) {
                    return '';
                }
                $title = QueueReportPeriods::metricLabel($metric).' — '.$b['title'];

                return '<div class="mt-0.5 text-xs leading-none whitespace-nowrap"><span class="'.e($b['class']).'" title="'.e($title).'">'.e($b['text']).'</span></div>';
            };

            /**
             * Render a sort indicator for a given column field.
             * Returns an inline SVG chevron: filled when active, faded when not.
             */
            $sortIcon = function (string $field) use ($sortField, $sortDirection): string {
                $active = $field === $sortField;
                $color = $active ? 'text-primary-600 dark:text-primary-400' : 'text-gray-300 dark:text-gray-600';
                $path = 'M8 4l4 5H4l4-5z';
                if ($active) {
                    $path = $sortDirection === 'asc'
                        ? 'M8 4l4 5H4l4-5z'   // up
                        : 'M8 12l-4-5h8l-4 5z'; // down
                }
                return '<svg xmlns="http://www.w3.org/2000/svg" class="inline-block ml-1 h-3 w-3 '.$color.'" viewBox="0 0 16 16" fill="currentColor"><path d="'.$path.'"/></svg>';
            };
        @endphp
        {{-- Biggest changes between consecutive periods --}}
        @if (! empty($visibleAnomalies))
            <div wire:loading.remove wire:target="{{ $loadingTargets }}" x-data="{ all: false }"
                class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-4">
                <div class="flex flex-wrap items-baseline justify-between gap-2 mb-3">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $tp('anomalies_title') }}</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $tp('anomalies_subtitle') }}</p>
                    </div>
                    @if (count($visibleAnomalies) > 6)
                        <button type="button" x-on:click="all = ! all"
                            class="text-xs font-medium text-primary-600 dark:text-primary-400 hover:underline">
                            <span x-show="! all">{{ $tp('anomalies_show_all', ['count' => count($visibleAnomalies)]) }}</span>
                            <span x-show="all" x-cloak>{{ $tp('anomalies_show_less') }}</span>
                        </button>
                    @endif
                </div>
                <ul class="divide-y divide-gray-100 dark:divide-gray-700/50">
                    @foreach ($visibleAnomalies as $i => $anomaly)
                        @php
                            $metric = (string) ($anomaly['metric'] ?? '');
                            $change = QueueReportPeriods::badge($metric, $anomaly, $breakdown, $periodLabels);
                            $isExt = ($anomaly['keyType'] ?? null) == 0;
                        @endphp
                        <li @if ($i >= 6) x-show="all" x-cloak @endif
                            class="flex flex-wrap items-center gap-x-3 gap-y-1 py-2 text-sm">
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold {{ QueueReportPeriods::severityClass($anomaly['severity'] ?? null) }}">
                                {{ $tp('severity_label_'.(($anomaly['severity'] ?? null) === 'major' ? 'major' : 'notable')) }}
                            </span>
                            <span class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                {{ $tp('anomalies_vs', [
                                    'current' => $periodLabels[$anomaly['period'] ?? ''] ?? ($anomaly['period'] ?? ''),
                                    'previous' => $periodLabels[$anomaly['previous_period'] ?? ''] ?? ($anomaly['previous_period'] ?? ''),
                                ]) }}
                            </span>
                            <span class="font-medium text-gray-800 dark:text-gray-200">
                                {{ trim(($anomaly['queue'] ?? '').' '.($anomaly['queueName'] ?? '')) }}@if ($isExt)<span class="font-normal text-gray-500 dark:text-gray-400"> · {{ trim(($anomaly['element'] ?? '').' '.($anomaly['elementName'] ?? '')) }}</span>@endif
                            </span>
                            <span class="text-gray-600 dark:text-gray-300">{{ QueueReportPeriods::metricLabel($metric) }}</span>
                            <span class="text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                {{ QueueReportPeriods::formatValue($metric, $anomaly['previous'] ?? null) }} → {{ QueueReportPeriods::formatValue($metric, $anomaly['current'] ?? null) }}
                            </span>
                            @if ($change)
                                <span class="font-semibold whitespace-nowrap {{ QueueReportPeriods::assessmentTextClass($anomaly['assessment'] ?? 'neutral') }}"
                                    @if (! empty($anomaly['normalized'])) title="{{ $tp('badge_normalized') }}" @endif>
                                    {{ $change['text'] }}
                                </span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div wire:loading.remove wire:target="{{ $loadingTargets }}"
            class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">

                        {{-- Column headers with sort --}}
                        <tr>
                            @if ($bucketed)
                                <th @if (! $this->groupsRowsByPeriod()) wire:click="sortBy('day')" @endif
                                    class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider select-none whitespace-nowrap {{ $this->groupsRowsByPeriod() ? '' : 'cursor-pointer hover:text-gray-700 dark:hover:text-gray-200' }}">
                                    {{ $breakdown === 'day' ? __('expert-statistics::pbx.expert_statistics.col_date') : $tp('col_period') }}@if (! $this->groupsRowsByPeriod()){!! $sortIcon('day') !!}@endif
                                </th>
                            @endif

                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">
                                {{ __('expert-statistics::pbx.expert_statistics.col_queue') }}
                            </th>

                            @if (! $queuesOnlyStats)
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">
                                    {{ __('expert-statistics::pbx.expert_statistics.col_extension') }}
                                </th>
                            @endif

                            <th wire:click="sortBy('calls')"
                                class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-200 select-none whitespace-nowrap">
                                {{ __('expert-statistics::pbx.expert_statistics.col_calls') }}{!! $sortIcon('calls') !!}
                            </th>

                            <th wire:click="sortBy('declined')"
                                class="px-4 py-3 text-center text-xs font-semibold text-red-500 dark:text-red-400 uppercase tracking-wider cursor-pointer hover:text-red-700 dark:hover:text-red-200 select-none">
                                <div class="whitespace-nowrap">
                                    {{ __('expert-statistics::pbx.expert_statistics.col_declined') }}{!! $sortIcon('declined') !!}
                                </div>
                                <div class="grid grid-cols-2 gap-1 text-xs font-normal normal-case mt-1 text-gray-500 dark:text-gray-400">
                                    <span>{{ $tp('sub_declined') }}</span>
                                    <span>{{ $tp('sub_abandoned') }}</span>
                                </div>
                            </th>

                            @if ($queuesOnlyStats)
                                <th wire:click="sortBy('internal_answered')"
                                    class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-200 select-none whitespace-nowrap">
                                    {{ __('expert-statistics::pbx.expert_statistics.col_internal') }}{!! $sortIcon('internal_answered') !!}
                                </th>
                            @else
                                <th wire:click="sortBy('solicited')"
                                    class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-200 select-none whitespace-nowrap">
                                    {{ __('expert-statistics::pbx.expert_statistics.col_solicited') }}{!! $sortIcon('solicited') !!}
                                </th>
                            @endif

                            <th wire:click="sortBy('answered')"
                                class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-200 select-none whitespace-nowrap">
                                {{ __('expert-statistics::pbx.expert_statistics.col_answered') }}{!! $sortIcon('answered') !!}
                            </th>

                            <th wire:click="sortBy('transferred')"
                                class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-200 select-none whitespace-nowrap">
                                {{ __('expert-statistics::pbx.expert_statistics.col_transferred') }}{!! $sortIcon('transferred') !!}
                            </th>

                            <th wire:click="sortBy('answered_percentage')"
                                class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-200 select-none whitespace-nowrap">
                                {{ __('expert-statistics::pbx.expert_statistics.col_answer_rate') }}{!! $sortIcon('answered_percentage') !!}
                            </th>

                            <th wire:click="sortBy('avg_talking')" title="{{ $tp('col_avg_talk_hint') }}"
                                class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-200 select-none whitespace-nowrap">
                                {{ $tp('col_avg_talk') }}{!! $sortIcon('avg_talking') !!}
                            </th>

                            <th wire:click="sortBy('avg_waiting')" title="{{ $tp('col_avg_wait_hint') }}"
                                class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-200 select-none whitespace-nowrap">
                                {{ $tp('col_avg_wait') }}{!! $sortIcon('avg_waiting') !!}
                            </th>
                        </tr>

                        {{-- Filter row --}}
                        <tr class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
                            @if ($bucketed)<td class="px-2 py-1"></td>@endif

                            {{-- Queue filter --}}
                            <td class="px-2 py-1">
                                <input
                                    wire:model.live.debounce.300ms="filterQueue"
                                    type="text"
                                    placeholder="{{ $tp('filter_queue') }}"
                                    class="w-full text-xs rounded border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-200 placeholder-gray-400 px-2 py-1 focus:ring-1 focus:ring-primary-500 focus:border-primary-500 focus:outline-none" />
                            </td>

                            {{-- Extension filter --}}
                            @if (! $queuesOnlyStats)
                                <td class="px-2 py-1">
                                    <input
                                        wire:model.live.debounce.300ms="filterExtension"
                                        type="text"
                                        placeholder="{{ $tp('filter_extension') }}"
                                        class="w-full text-xs rounded border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-200 placeholder-gray-400 px-2 py-1 focus:ring-1 focus:ring-primary-500 focus:border-primary-500 focus:outline-none" />
                                </td>
                            @endif

                            {{-- Empty cells for remaining columns --}}
                            @php $filterColspan = $queuesOnlyStats ? 8 : 7; @endphp
                            <td colspan="{{ $filterColspan }}" class="px-2 py-1"></td>
                        </tr>

                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                        @if (empty($rows))
                            <tr>
                                <td colspan="{{ $colCount }}" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">
                                    {{ __('expert-statistics::pbx.expert_statistics.no_data') }}
                                </td>
                            </tr>
                        @endif
                        @foreach ($groups as $group)
                            @if ($group['key'] !== null)
                                {{-- Period sub-header (week / month views) --}}
                                <tr class="bg-gray-100 dark:bg-gray-900/60" wire:key="period-{{ $group['key'] }}">
                                    <td colspan="{{ $colCount }}" class="px-4 py-2 text-xs font-semibold text-gray-700 dark:text-gray-200 whitespace-nowrap">
                                        <span>{{ $group['label'] }}</span>
                                        @if ($group['partial'])
                                            <span class="ml-1 font-normal text-amber-600 dark:text-amber-400 cursor-help" title="{{ QueueReportPeriods::partialTooltip($group['start'], $group['end']) }}">* {{ $tp('partial_badge') }}</span>
                                        @endif
                                        <span class="ml-2 font-normal text-gray-500 dark:text-gray-400">
                                            {{ QueueReportPeriods::formatDay((string) $group['start']) }} – {{ QueueReportPeriods::formatDay((string) $group['end']) }}
                                        </span>
                                    </td>
                                </tr>
                            @endif
                        @foreach ($group['rows'] as $row)
                            @php
                                $isQueue = ($row['keyType'] ?? null) == 4;
                                $calls = (int) ($row['calls'] ?? 0);
                                $answered = (int) ($row['answered'] ?? 0);
                                $abandoned = (int) ($row['abandoned'] ?? 0);
                                $preanswerAbandoned = (int) ($row['preanswer_abandoned'] ?? 0);
                                $declined = max(0, $calls - $answered - $abandoned);
                                $divisor = $calls - $preanswerAbandoned;
                                $pct = $divisor > 0 ? ($answered / $divisor) * 100 : 0;

                                // Queue rows filter on the queue as destination; extension rows on the
                                // extension as destination, reached through its parent queue as origin.
                                $queueDn = explode('-', $row['queueNameNumber'] ?? '')[0];
                                $elemDn  = explode('-', $row['elementNameNumber'] ?? '')[0];
                                // Day / week / month rows link to the calls of their own period.
                                $rowRange = $bucketed ? CallAnalysisLink::rowRange($row) : null;
                                $cfaQE = array_merge(CallAnalysisLink::period($rowRange[0] ?? $startDate, $rowRange[1] ?? $endDate, $startTime, $endTime), [
                                    'callWay' => 'inbound',
                                    'destinationDn' => $isQueue ? $queueDn : $elemDn, 'destinationDnType' => $isQueue ? '4' : '0',
                                    'originDn' => $isQueue ? '' : $queueDn, 'originDnType' => $isQueue ? '' : '4',
                                ]);
                                $urlAll        = CallAnalysisLink::url([...$cfaQE, 'callStatus' => 'all']);
                                $urlAnswered   = CallAnalysisLink::url([...$cfaQE, 'callStatus' => 'answered']);
                                // Declined and abandoned share this filter: call analysis can't tell them apart.
                                $urlUnanswered = CallAnalysisLink::url([...$cfaQE, 'callStatus' => 'unanswered']);
                            @endphp
                            <tr
                                class="{{ $isQueue && ! $queuesOnlyStats ? 'bg-gray-50/50 dark:bg-gray-900/20 font-medium' : '' }} hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                @if ($bucketed)
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                        <span>{{ QueueReportPeriods::rowLabel($breakdown, $row) }}</span>@if (! empty($row['partial']))<span class="text-amber-600 dark:text-amber-400 cursor-help" title="{{ QueueReportPeriods::partialTooltip($row['period_start'] ?? null, $row['period_end'] ?? null) }}">*</span>@endif
                                    </td>
                                @endif
                                <td class="px-4 py-3 text-gray-800 dark:text-gray-200 whitespace-nowrap">
                                    {{ $row['queueNameNumber'] ?? '—' }}
                                </td>
                                @if (! $queuesOnlyStats)
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                        {{ $isQueue ? '—' : ($row['elementNameNumber'] ?? '—') }}
                                    </td>
                                @endif
                                <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">
                                    <x-expert-statistics::call-analysis-link :href="$urlAll">{{ $calls }}</x-expert-statistics::call-analysis-link>
                                    {!! $badge($row, 'calls') !!}
                                </td>
                                <td class="px-4 py-3 text-red-600 dark:text-red-400">
                                    <div class="grid grid-cols-2 gap-2 text-right">
                                        <span><x-expert-statistics::call-analysis-link :href="$urlUnanswered">{{ $declined }}</x-expert-statistics::call-analysis-link></span>
                                        <span><x-expert-statistics::call-analysis-link :href="$urlUnanswered">{{ $abandoned }}{{ $preanswerAbandoned > 0 ? ' ('.$preanswerAbandoned.')' : '' }}</x-expert-statistics::call-analysis-link>{!! $badge($row, 'abandoned') !!}</span>
                                    </div>
                                </td>
                                @if ($queuesOnlyStats)
                                    <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-400">
                                        {{ (int) ($row['internal_answered'] ?? 0) }}
                                    </td>
                                @else
                                    <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-400">
                                        {{ (int) ($row['solicited'] ?? 0) }}
                                    </td>
                                @endif
                                <td class="px-4 py-3 text-right text-green-600 dark:text-green-400">
                                    <x-expert-statistics::call-analysis-link :href="$urlAnswered">{{ $answered }}</x-expert-statistics::call-analysis-link>
                                    {!! $badge($row, 'answered') !!}
                                </td>
                                <td class="px-4 py-3 text-right text-blue-600 dark:text-blue-400">
                                    {{ (int) ($row['transferred'] ?? 0) }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                        {{ $pct >= 80
                                            ? 'bg-green-100 text-green-700 dark:bg-green-950/40 dark:text-green-400'
                                            : ($pct >= 60
                                                ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-950/40 dark:text-yellow-400'
                                                : 'bg-red-100 text-red-700 dark:bg-red-950/40 dark:text-red-400') }}">
                                        {{ number_format($pct, 1) }}%
                                    </span>
                                    {!! $badge($row, 'answer_rate') !!}
                                </td>
                                <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-400">
                                    @php($avgTalk = \CXEngine\ExpertStatistics\Support\QueueReportPeriods::averageTalking($row))
                                    {{ $avgTalk === null ? '–' : $this->formatDuration((int) round($avgTalk)) }}
                                </td>
                                <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-400">
                                    @php($avgWait = \CXEngine\ExpertStatistics\Support\QueueReportPeriods::averageWaiting($row))
                                    {{ $avgWait === null ? '–' : $this->formatDuration((int) round($avgWait)) }}
                                    {!! $badge($row, 'avg_waiting') !!}
                                </td>
                            </tr>
                        @endforeach
                        @endforeach
                    </tbody>

                </table>
            </div>
        </div>
    @endif

    <livewire:expert-statistics.reports.share-report-modal :key="'expert-statistics.reports.share-report-modal'" />

</div>

</x-expert-statistics::cluster-layout>
