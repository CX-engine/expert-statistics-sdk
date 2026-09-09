<div class="space-y-5">
    <div class="flex justify-between items-center">
        {{-- Element selector --}}
        <x-expert-statistics::element-selector :urlType="$urlType" :pbxElements="$pbxElements"
            :selectedElements="$selectedElements" />
    </div>

    {{-- Filter bar --}}
    <x-expert-statistics::filter-bar
        :selectedPeriod="$selectedPeriod"
        :startDate="$startDate"
        :endDate="$endDate"
        :startTime="$startTime"
        :endTime="$endTime"
        :showTimeRange="true"
    />

    @if ($errorMessage)
        <div class="rounded-xl bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-900 px-4 py-3 text-sm text-red-700 dark:text-red-400">
            {{ $errorMessage }}
        </div>
    @endif

    @php
        $loadingTargets = 'applyFilters,selectPeriod,setCustomRange,updateTimeRange,addElement,applyPbxElementSelection,selectAllPbxElements,clearPbxElements,removePbxElement,setTab,setViewBy,toggleQueueFilter,clearQueueFilter,goToPage';
    @endphp

    {{-- Tabs: By Group / Ungrouped Numbers --}}
    @if ($dn || !empty($groupSelectedName))
    <div class="flex flex-wrap gap-4 items-center justify-between">
        {{-- Tab switcher --}}
        <div class="flex bg-gray-100 dark:bg-gray-800 rounded-xl p-1 gap-1">
            <button wire:click="setTab('groups')" type="button"
                class="px-4 py-2 text-sm font-medium rounded-lg transition-all
                    {{ $activeTab === 'groups'
                        ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm'
                        : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200' }}">
                {{ __('expert-statistics::pbx.expert_statistics.caller_numbers_tab_groups') }}
            </button>
            <button wire:click="setTab('groupless')" type="button"
                class="px-4 py-2 text-sm font-medium rounded-lg transition-all
                    {{ $activeTab === 'groupless'
                        ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm'
                        : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200' }}">
                {{ __('expert-statistics::pbx.expert_statistics.caller_numbers_tab_groupless') }}
            </button>
        </div>

        <div class="flex flex-wrap gap-3 items-center">
            {{-- View toggle: By Caller / By Queue --}}
            <div class="flex bg-gray-100 dark:bg-gray-800 rounded-xl p-1 gap-1">
                <button wire:click="setViewBy('group')" type="button"
                    class="px-4 py-2 text-sm font-medium rounded-lg transition-all
                        {{ $viewBy === 'group'
                            ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm'
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200' }}">
                    {{ __('expert-statistics::pbx.expert_statistics.caller_numbers_view_by_group') }}
                </button>
                <button wire:click="setViewBy('queue')" type="button"
                    class="px-4 py-2 text-sm font-medium rounded-lg transition-all
                        {{ $viewBy === 'queue'
                            ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm'
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200' }}">
                    {{ __('expert-statistics::pbx.expert_statistics.caller_numbers_view_by_queue') }}
                </button>
            </div>

            {{-- Consolidated toggle (caller view only) --}}
            @if ($viewBy === 'group' && ($activeTab === 'groups' ? !empty($tableData) : !empty($grouplessTableData)))
            <label class="inline-flex items-center gap-2 cursor-pointer">
                <input
                    type="checkbox"
                    wire:model.live="showConsolidated"
                    class="rounded border-gray-300 dark:border-gray-600 text-primary-600"
                />
                <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('expert-statistics::pbx.expert_statistics.caller_numbers_consolidated') }}</span>
            </label>
            @endif

            {{-- Exclude closed hours toggle --}}
            <label class="inline-flex items-center gap-2 cursor-pointer">
                <input
                    type="checkbox"
                    wire:model.live="excludeClosedHours"
                    class="rounded border-gray-300 dark:border-gray-600 text-primary-600"
                />
                <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('expert-statistics::pbx.expert_statistics.exclude_closed_hours') }}</span>
            </label>
        </div>
    </div>

    {{-- Queue filter pills --}}
    @if (!empty($availableQueues))
    <div class="flex flex-wrap gap-2 items-center">
        <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
            {{ __('expert-statistics::pbx.expert_statistics.caller_numbers_queue_filter') }}:
        </span>
        @foreach ($availableQueues as $qDn)
        <button
            wire:click="toggleQueueFilter('{{ $qDn }}')"
            type="button"
            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium transition-all
                {{ in_array($qDn, $selectedQueues)
                    ? 'bg-primary-100 text-primary-700 dark:bg-primary-950/40 dark:text-primary-400 ring-1 ring-primary-300 dark:ring-primary-700'
                    : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
            {{ $queueNameMap[$qDn] ?? $qDn }}
            @if (in_array($qDn, $selectedQueues))
            <x-heroicon-m-x-mark class="w-3 h-3" />
            @endif
        </button>
        @endforeach
        @if (!empty($selectedQueues))
        <button wire:click="clearQueueFilter" type="button"
            class="text-xs text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 underline">
            {{ __('expert-statistics::pbx.expert_statistics.caller_numbers_queue_filter_all') }}
        </button>
        @endif
    </div>
    @endif
    @endif

    {{-- Loading --}}
    <div wire:loading.flex wire:target="{{ $loadingTargets }}" class="items-center justify-center py-10">
        <svg class="animate-spin h-8 w-8 text-primary-500" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
        </svg>
    </div>

    {{-- Empty state: no DN entered --}}
    @if (! $dn && empty($groupSelectedName))
    <div wire:loading.remove wire:target="{{ $loadingTargets }}" class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-12 text-center">
        <x-heroicon-o-user-group class="w-12 h-12 mx-auto text-gray-200 dark:text-gray-700 mb-4" />
        <h3 class="text-base font-semibold text-gray-600 dark:text-gray-400 mb-2">{{ __('expert-statistics::pbx.expert_statistics.caller_numbers_title') }}</h3>
        <p class="text-sm text-gray-400 dark:text-gray-500">{{ __('expert-statistics::pbx.expert_statistics.caller_numbers_empty') }}</p>
        <p class="text-xs text-gray-300 dark:text-gray-600 mt-2">
            {{ __('expert-statistics::pbx.expert_statistics.caller_numbers_empty_note') }}
            {{ __('expert-statistics::pbx.expert_statistics.caller_numbers_settings_link') }}.
        </p>
    </div>

    @elseif (! $this->hasData())
    <div wire:loading.remove wire:target="{{ $loadingTargets }}" class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-10 text-center">
        <x-heroicon-o-chart-bar class="w-10 h-10 mx-auto text-gray-200 dark:text-gray-700 mb-3" />
        <p class="text-sm text-gray-400 dark:text-gray-500">
            @if ($activeTab === 'groupless')
                {{ __('expert-statistics::pbx.expert_statistics.caller_numbers_groupless_empty') }}
            @else
                {{ __('expert-statistics::pbx.expert_statistics.caller_numbers_no_data') }}
            @endif
        </p>
    </div>

    @else
    {{-- Data table --}}
    <div wire:loading.remove wire:target="{{ $loadingTargets }}" class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden">
        <div class="overflow-x-auto">
            @php
                $sortIcon = function (string $field) use ($sortField, $sortDirection): string {
                    $active = $field === $sortField;
                    $color = $active ? 'text-primary-600 dark:text-primary-400' : 'text-gray-300 dark:text-gray-600';
                    $path = 'M8 4l4 4H4l4-4zm0 8l-4-4h8l-4 4z';
                    if ($active) {
                        $path = $sortDirection === 'asc' ? 'M8 4l4 5H4l4-5z' : 'M8 12l-4-5h8l-4 5z';
                    }
                    return '<svg xmlns="http://www.w3.org/2000/svg" class="inline-block ml-1 h-3 w-3 '.$color.'" viewBox="0 0 16 16" fill="currentColor"><path d="'.$path.'"/></svg>';
                };
            @endphp
            @if ($viewBy === 'queue')
            {{-- Queue view table --}}
            @php $rows = $this->getDisplayData(); @endphp
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th wire:click="sortBy('queue_dn')" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-200 select-none whitespace-nowrap">{{ __('expert-statistics::pbx.expert_statistics.caller_numbers_col_queue') }}{!! $sortIcon('queue_dn') !!}</th>
                        <th wire:click="sortBy('inbound_total')" class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-200 select-none whitespace-nowrap">{{ __('expert-statistics::pbx.expert_statistics.caller_numbers_col_total') }}{!! $sortIcon('inbound_total') !!}</th>
                        <th wire:click="sortBy('inbound_answered')" class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-200 select-none whitespace-nowrap">{{ __('expert-statistics::pbx.expert_statistics.col_answered') }}{!! $sortIcon('inbound_answered') !!}</th>
                        <th wire:click="sortBy('inbound_unanswered')" class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-200 select-none whitespace-nowrap">{{ __('expert-statistics::pbx.expert_statistics.caller_numbers_col_unanswered') }}{!! $sortIcon('inbound_unanswered') !!}</th>
                        <th wire:click="sortBy('response_rate')" class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-200 select-none whitespace-nowrap">{{ __('expert-statistics::pbx.expert_statistics.col_answer_rate') }}{!! $sortIcon('response_rate') !!}</th>
                        <th wire:click="sortBy('talking_duration_total')" class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-200 select-none whitespace-nowrap">{{ __('expert-statistics::pbx.expert_statistics.caller_numbers_col_duration_total') }}{!! $sortIcon('talking_duration_total') !!}</th>
                        <th wire:click="sortBy('talking_duration_avg')" class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-200 select-none whitespace-nowrap">{{ __('expert-statistics::pbx.expert_statistics.caller_numbers_col_duration_avg') }}{!! $sortIcon('talking_duration_avg') !!}</th>
                        <th wire:click="sortBy('waiting_duration_total')" class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-200 select-none whitespace-nowrap">{{ __('expert-statistics::pbx.expert_statistics.caller_numbers_col_wait_total') }}{!! $sortIcon('waiting_duration_total') !!}</th>
                        <th wire:click="sortBy('waiting_duration_avg')" class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-200 select-none whitespace-nowrap">{{ __('expert-statistics::pbx.expert_statistics.caller_numbers_col_wait_avg') }}{!! $sortIcon('waiting_duration_avg') !!}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                    @foreach ($rows as $row)
                    @php
                        $rate = (float) ($row['response_rate'] ?? 0);
                        $qDn = explode('-', $row['queue_dn'] ?? '')[0];
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                        <td class="px-4 py-3 font-medium text-gray-800 dark:text-gray-200 whitespace-nowrap">{{ $queueNameMap[$qDn] ?? ($row['queue_dn'] ?? '—') }}</td>
                        <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">{{ $row['inbound_total'] ?? 0 }}</td>
                        <td class="px-4 py-3 text-right text-green-600 dark:text-green-400">{{ $row['inbound_answered'] ?? 0 }}</td>
                        <td class="px-4 py-3 text-right text-red-600 dark:text-red-400">{{ $row['inbound_unanswered'] ?? 0 }}</td>
                        <td class="px-4 py-3 text-right">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                {{ $rate >= 80 ? 'bg-green-100 text-green-700 dark:bg-green-950/40 dark:text-green-400' : ($rate >= 60 ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-950/40 dark:text-yellow-400' : 'bg-red-100 text-red-700 dark:bg-red-950/40 dark:text-red-400') }}">
                                {{ number_format($rate, 1) }}%
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-400">{{ $this->formatDuration($row['talking_duration_total'] ?? 0) }}</td>
                        <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-400">{{ $this->formatDuration($row['talking_duration_avg'] ?? 0) }}</td>
                        <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-400">{{ $this->formatDuration($row['waiting_duration_total'] ?? 0) }}</td>
                        <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-400">{{ $this->formatDuration($row['waiting_duration_avg'] ?? 0) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            @elseif ($activeTab === 'groupless')
            {{-- Groupless individual callers table --}}
            @php $rows = $this->getDisplayData(); @endphp
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th wire:click="sortBy('caller_number')" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-200 select-none whitespace-nowrap">{{ __('expert-statistics::pbx.expert_statistics.caller_numbers_col_caller') }}{!! $sortIcon('caller_number') !!}</th>
                        <th wire:click="sortBy('inbound_total')" class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-200 select-none whitespace-nowrap">{{ __('expert-statistics::pbx.expert_statistics.caller_numbers_col_total') }}{!! $sortIcon('inbound_total') !!}</th>
                        <th wire:click="sortBy('inbound_answered')" class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-200 select-none whitespace-nowrap">{{ __('expert-statistics::pbx.expert_statistics.col_answered') }}{!! $sortIcon('inbound_answered') !!}</th>
                        <th wire:click="sortBy('inbound_unanswered')" class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-200 select-none whitespace-nowrap">{{ __('expert-statistics::pbx.expert_statistics.caller_numbers_col_unanswered') }}{!! $sortIcon('inbound_unanswered') !!}</th>
                        <th wire:click="sortBy('response_rate')" class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-200 select-none whitespace-nowrap">{{ __('expert-statistics::pbx.expert_statistics.col_answer_rate') }}{!! $sortIcon('response_rate') !!}</th>
                        <th wire:click="sortBy('talking_duration_total')" class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-200 select-none whitespace-nowrap">{{ __('expert-statistics::pbx.expert_statistics.caller_numbers_col_duration_total') }}{!! $sortIcon('talking_duration_total') !!}</th>
                        <th wire:click="sortBy('talking_duration_avg')" class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-200 select-none whitespace-nowrap">{{ __('expert-statistics::pbx.expert_statistics.caller_numbers_col_duration_avg') }}{!! $sortIcon('talking_duration_avg') !!}</th>
                        <th wire:click="sortBy('waiting_duration_total')" class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-200 select-none whitespace-nowrap">{{ __('expert-statistics::pbx.expert_statistics.caller_numbers_col_wait_total') }}{!! $sortIcon('waiting_duration_total') !!}</th>
                        <th wire:click="sortBy('waiting_duration_avg')" class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-200 select-none whitespace-nowrap">{{ __('expert-statistics::pbx.expert_statistics.caller_numbers_col_wait_avg') }}{!! $sortIcon('waiting_duration_avg') !!}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                    @foreach ($rows as $row)
                    @php
                        $rate = (float) ($row['response_rate'] ?? 0);
                        $callerNum = $row['caller_number'] ?? '';
                        $isTotal = $callerNum === 'TOTAL';
                    @endphp
                    <tr class="{{ $isTotal ? 'bg-gray-50 dark:bg-gray-900/30 font-semibold' : 'hover:bg-gray-50 dark:hover:bg-gray-700/30' }} transition-colors">
                        <td class="px-4 py-3 {{ $isTotal ? '' : 'font-mono text-xs' }} text-gray-800 dark:text-gray-200 whitespace-nowrap">{{ $callerNum ?: '—' }}</td>
                        <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">{{ $row['inbound_total'] ?? 0 }}</td>
                        <td class="px-4 py-3 text-right text-green-600 dark:text-green-400">{{ $row['inbound_answered'] ?? 0 }}</td>
                        <td class="px-4 py-3 text-right text-red-600 dark:text-red-400">{{ $row['inbound_unanswered'] ?? 0 }}</td>
                        <td class="px-4 py-3 text-right">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                {{ $rate >= 80 ? 'bg-green-100 text-green-700 dark:bg-green-950/40 dark:text-green-400' : ($rate >= 60 ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-950/40 dark:text-yellow-400' : 'bg-red-100 text-red-700 dark:bg-red-950/40 dark:text-red-400') }}">
                                {{ number_format($rate, 1) }}%
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-400">{{ $this->formatDuration($row['talking_duration_total'] ?? 0) }}</td>
                        <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-400">{{ $this->formatDuration($row['talking_duration_avg'] ?? 0) }}</td>
                        <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-400">{{ $this->formatDuration($row['waiting_duration_total'] ?? 0) }}</td>
                        <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-400">{{ $this->formatDuration($row['waiting_duration_avg'] ?? 0) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            @else
            {{-- Groups view table --}}
            @php $displayRows = $this->getDisplayData(); @endphp
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th wire:click="sortBy('caller_number')" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-200 select-none whitespace-nowrap">{{ __('expert-statistics::pbx.expert_statistics.caller_numbers_col_caller') }}{!! $sortIcon('caller_number') !!}</th>
                        <th wire:click="sortBy('inbound_total')" class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-200 select-none whitespace-nowrap">{{ __('expert-statistics::pbx.expert_statistics.caller_numbers_col_total') }}{!! $sortIcon('inbound_total') !!}</th>
                        <th wire:click="sortBy('inbound_answered')" class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-200 select-none whitespace-nowrap">{{ __('expert-statistics::pbx.expert_statistics.col_answered') }}{!! $sortIcon('inbound_answered') !!}</th>
                        <th wire:click="sortBy('inbound_unanswered')" class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-200 select-none whitespace-nowrap">{{ __('expert-statistics::pbx.expert_statistics.caller_numbers_col_unanswered') }}{!! $sortIcon('inbound_unanswered') !!}</th>
                        <th wire:click="sortBy('response_rate')" class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-200 select-none whitespace-nowrap">{{ __('expert-statistics::pbx.expert_statistics.col_answer_rate') }}{!! $sortIcon('response_rate') !!}</th>
                        <th wire:click="sortBy('talking_duration_total')" class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-200 select-none whitespace-nowrap">{{ __('expert-statistics::pbx.expert_statistics.caller_numbers_col_duration_total') }}{!! $sortIcon('talking_duration_total') !!}</th>
                        <th wire:click="sortBy('talking_duration_avg')" class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-200 select-none whitespace-nowrap">{{ __('expert-statistics::pbx.expert_statistics.caller_numbers_col_duration_avg') }}{!! $sortIcon('talking_duration_avg') !!}</th>
                        <th wire:click="sortBy('waiting_duration_total')" class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-200 select-none whitespace-nowrap">{{ __('expert-statistics::pbx.expert_statistics.caller_numbers_col_wait_total') }}{!! $sortIcon('waiting_duration_total') !!}</th>
                        <th wire:click="sortBy('waiting_duration_avg')" class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-200 select-none whitespace-nowrap">{{ __('expert-statistics::pbx.expert_statistics.caller_numbers_col_wait_avg') }}{!! $sortIcon('waiting_duration_avg') !!}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                    @foreach ($displayRows as $row)
                    @php
                        $isTotal = ($row['caller_number'] ?? '') === 'TOTAL';
                        $rate = (float) ($row['response_rate'] ?? 0);
                    @endphp
                    <tr class="{{ $isTotal ? 'bg-gray-50 dark:bg-gray-900/30 font-semibold' : 'hover:bg-gray-50 dark:hover:bg-gray-700/30' }} transition-colors">
                        <td class="px-4 py-3 text-gray-800 dark:text-gray-200 whitespace-nowrap">{{ $row['caller_number'] ?? '—' }}</td>
                        <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">{{ $row['inbound_total'] ?? 0 }}</td>
                        <td class="px-4 py-3 text-right text-green-600 dark:text-green-400">{{ $row['inbound_answered'] ?? 0 }}</td>
                        <td class="px-4 py-3 text-right text-red-600 dark:text-red-400">{{ $row['inbound_unanswered'] ?? 0 }}</td>
                        <td class="px-4 py-3 text-right">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                {{ $rate >= 80 ? 'bg-green-100 text-green-700 dark:bg-green-950/40 dark:text-green-400' : ($rate >= 60 ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-950/40 dark:text-yellow-400' : 'bg-red-100 text-red-700 dark:bg-red-950/40 dark:text-red-400') }}">
                                {{ number_format($rate, 1) }}%
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-400">{{ $this->formatDuration($row['talking_duration_total'] ?? 0) }}</td>
                        <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-400">{{ $this->formatDuration($row['talking_duration_avg'] ?? 0) }}</td>
                        <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-400">{{ $this->formatDuration($row['waiting_duration_total'] ?? 0) }}</td>
                        <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-400">{{ $this->formatDuration($row['waiting_duration_avg'] ?? 0) }}</td>
                    </tr>
                    @endforeach
                </tbody>

                {{-- Totals row (groups view, non-consolidated mode) --}}
                @if (! $showConsolidated && ! empty($consolidated))
                <tfoot class="border-t-2 border-gray-300 dark:border-gray-600">
                    <tr class="bg-gray-100 dark:bg-gray-900/40 font-semibold">
                        <td class="px-4 py-3 text-gray-800 dark:text-gray-200">{{ __('expert-statistics::pbx.expert_statistics.caller_numbers_total_row') }}</td>
                        <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">{{ $consolidated['inbound_total'] ?? 0 }}</td>
                        <td class="px-4 py-3 text-right text-green-600 dark:text-green-400">{{ $consolidated['inbound_answered'] ?? 0 }}</td>
                        <td class="px-4 py-3 text-right text-red-600 dark:text-red-400">{{ $consolidated['inbound_unanswered'] ?? 0 }}</td>
                        <td class="px-4 py-3 text-right">
                            @php $rate = (float) ($consolidated['response_rate'] ?? 0); @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                {{ $rate >= 80 ? 'bg-green-100 text-green-700 dark:bg-green-950/40 dark:text-green-400' : ($rate >= 60 ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-950/40 dark:text-yellow-400' : 'bg-red-100 text-red-700 dark:bg-red-950/40 dark:text-red-400') }}">
                                {{ number_format($rate, 1) }}%
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-400">{{ $this->formatDuration($consolidated['talking_duration_total'] ?? 0) }}</td>
                        <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-400">{{ $this->formatDuration($consolidated['talking_duration_avg'] ?? 0) }}</td>
                        <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-400">{{ $this->formatDuration($consolidated['waiting_duration_total'] ?? 0) }}</td>
                        <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-400">{{ $this->formatDuration($consolidated['waiting_duration_avg'] ?? 0) }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
            @endif
        </div>

        {{-- Pagination (hidden while the consolidated single-row view is active) --}}
        @if ($lastPage > 1 && ! ($showConsolidated && $viewBy === 'group'))
        <div class="flex items-center justify-between px-4 py-3 border-t border-gray-200 dark:border-gray-700">
            <p class="text-xs text-gray-500 dark:text-gray-400">
                @php
                    $from = ($currentPage - 1) * $perPage + 1;
                    $to   = min($currentPage * $perPage, $totalItems);
                @endphp
                {{ __('expert-statistics::pbx.expert_statistics.caller_numbers_pagination_showing', ['from' => $from, 'to' => $to, 'total' => $totalItems]) }}
            </p>
            <div class="flex items-center gap-1">
                <button
                    wire:click="goToPage({{ $currentPage - 1 }})"
                    @disabled($currentPage <= 1)
                    type="button"
                    class="px-3 py-1.5 text-xs rounded-lg border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400
                        hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                    {{ __('expert-statistics::pbx.expert_statistics.caller_numbers_pagination_prev') }}
                </button>

                @php
                    $rangeStart = max(1, $currentPage - 2);
                    $rangeEnd   = min($lastPage, $currentPage + 2);
                @endphp

                @if ($rangeStart > 1)
                <button wire:click="goToPage(1)" type="button"
                    class="px-3 py-1.5 text-xs rounded-lg border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">1</button>
                @if ($rangeStart > 2)
                <span class="px-1 text-gray-400 dark:text-gray-600">…</span>
                @endif
                @endif

                @for ($p = $rangeStart; $p <= $rangeEnd; $p++)
                <button
                    wire:click="goToPage({{ $p }})"
                    type="button"
                    class="px-3 py-1.5 text-xs rounded-lg border transition-colors
                        {{ $p === $currentPage
                            ? 'bg-primary-600 border-primary-600 text-white'
                            : 'border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                    {{ $p }}
                </button>
                @endfor

                @if ($rangeEnd < $lastPage)
                @if ($rangeEnd < $lastPage - 1)
                <span class="px-1 text-gray-400 dark:text-gray-600">…</span>
                @endif
                <button wire:click="goToPage({{ $lastPage }})" type="button"
                    class="px-3 py-1.5 text-xs rounded-lg border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">{{ $lastPage }}</button>
                @endif

                <button
                    wire:click="goToPage({{ $currentPage + 1 }})"
                    @disabled($currentPage >= $lastPage)
                    type="button"
                    class="px-3 py-1.5 text-xs rounded-lg border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400
                        hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                    {{ __('expert-statistics::pbx.expert_statistics.caller_numbers_pagination_next') }}
                </button>
            </div>
        </div>
        @endif
    </div>
    @endif

</div>
