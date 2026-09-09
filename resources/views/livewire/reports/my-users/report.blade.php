<div class="space-y-5">
    <div class="flex justify-between items-center">
        {{-- Element selector --}}
        <x-expert-statistics::element-selector
            :urlType="$urlType"
            :pbxElements="$pbxElements"
            :selectedElements="$selectedElements"
            :groupSelectedName="$groupSelectedName"
        />
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

    {{-- Toggles row --}}
    @if (! empty($tableData))
    <div class="flex flex-wrap gap-3 items-center justify-between">
        <div class="flex flex-wrap gap-3 items-center">

            {{-- Unique calls toggle --}}
            <x-expert-statistics::unique-calls-toggle
                :enabled="$showConsolidated"
                :checked="$uniqueCalls"
                wireModel="uniqueCalls"
            />

            {{-- Consolidated toggle --}}
            <label class="inline-flex items-center gap-2 cursor-pointer">
                <input
                    type="checkbox"
                    wire:model.live="showConsolidated"
                    class="rounded border-gray-300 dark:border-gray-600 text-primary-600"
                />
                <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('expert-statistics::pbx.expert_statistics.my_users_consolidated') }}</span>
            </label>

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

        {{-- Tab switcher --}}
        <div class="flex border-b border-gray-200 dark:border-gray-700">
            <button
                wire:click="switchTab('calls')"
                type="button"
                class="px-4 py-2 text-sm font-medium transition-all
                    {{ $activeTab === 'calls'
                        ? 'border-b-2 border-primary-600 text-primary-600'
                        : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200' }}"
            >
                {{ __('expert-statistics::pbx.expert_statistics.my_users_tab_calls') }}
            </button>
            <button
                wire:click="switchTab('status')"
                type="button"
                @if ($showConsolidated) disabled title="{{ __('expert-statistics::pbx.expert_statistics.my_users_status_disabled') }}" @endif
                class="px-4 py-2 text-sm font-medium transition-all
                    {{ $showConsolidated
                        ? 'text-gray-300 dark:text-gray-600 cursor-not-allowed'
                        : ($activeTab === 'status'
                            ? 'border-b-2 border-primary-600 text-primary-600'
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200') }}"
            >
                {{ __('expert-statistics::pbx.expert_statistics.my_users_tab_status') }}
            </button>
            <button
                wire:click="switchTab('queues')"
                type="button"
                @if ($showConsolidated) disabled title="{{ __('expert-statistics::pbx.expert_statistics.my_users_status_disabled') }}" @endif
                class="px-4 py-2 text-sm font-medium transition-all
                    {{ $showConsolidated
                        ? 'text-gray-300 dark:text-gray-600 cursor-not-allowed'
                        : ($activeTab === 'queues'
                            ? 'border-b-2 border-primary-600 text-primary-600'
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200') }}"
            >
                {{ __('expert-statistics::pbx.expert_statistics.my_users_tab_queues') }}
            </button>
        </div>
    </div>
    @endif

    @php
        $loadingTargets = 'applyFilters,selectPeriod,setCustomRange,updateTimeRange,applyPbxElementSelection,selectAllPbxElements,clearPbxElements,removePbxElement';
    @endphp

    {{-- Loading --}}
    <div wire:loading.flex wire:target="{{ $loadingTargets }}" class="items-center justify-center py-10">
        <div class="w-8 h-8 border-2 border-primary-500 border-t-transparent rounded-full animate-spin"></div>
    </div>

    {{-- Empty state --}}
    @if (! $dn)
    <div wire:loading.remove wire:target="{{ $loadingTargets }}" class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-12 text-center">
        <x-heroicon-o-user-group class="w-12 h-12 mx-auto text-gray-200 dark:text-gray-700 mb-4" />
        <h3 class="text-base font-semibold text-gray-600 dark:text-gray-400 mb-2">{{ __('expert-statistics::pbx.expert_statistics.my_users_title') }}</h3>
        <p class="text-sm text-gray-400 dark:text-gray-500">{{ __('expert-statistics::pbx.expert_statistics.my_users_empty') }}</p>
    </div>

    @elseif (empty($tableData))
    <div wire:loading.remove wire:target="{{ $loadingTargets }}" class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-10 text-center">
        <x-heroicon-o-chart-bar class="w-10 h-10 mx-auto text-gray-200 dark:text-gray-700 mb-3" />
        <p class="text-sm text-gray-400 dark:text-gray-500">{{ __('expert-statistics::pbx.expert_statistics.my_users_no_data') }}</p>
    </div>

    @else
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

    <div wire:loading.remove wire:target="{{ $loadingTargets }}" class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden">
        <div class="overflow-x-auto">

            @if ($activeTab === 'calls')
            {{-- Calls tab --}}
            @php $displayRows = $this->getDisplayData(); @endphp
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <th wire:click="sortBy('user')" class="px-4 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-200 select-none whitespace-nowrap" rowspan="2">{{ __('expert-statistics::pbx.expert_statistics.my_users_col_user') }}{!! $sortIcon('user') !!}</th>
                        <th class="px-4 py-2 text-center text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wider border-l border-gray-200 dark:border-gray-700" colspan="2">{{ __('expert-statistics::pbx.expert_statistics.my_users_group_outbound') }}</th>
                        <th class="px-4 py-2 text-center text-xs font-semibold text-green-600 dark:text-green-400 uppercase tracking-wider border-l border-gray-200 dark:border-gray-700" colspan="5">{{ __('expert-statistics::pbx.expert_statistics.my_users_group_inbound') }}</th>
                        <th class="px-4 py-2 text-center text-xs font-semibold text-purple-600 dark:text-purple-400 uppercase tracking-wider border-l border-gray-200 dark:border-gray-700" colspan="3">{{ __('expert-statistics::pbx.expert_statistics.my_users_group_internal') }}</th>
                        <th class="px-4 py-2 text-center text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider border-l border-gray-200 dark:border-gray-700" colspan="3">{{ __('expert-statistics::pbx.expert_statistics.my_users_group_total') }}</th>
                    </tr>
                    <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                        <th wire:click="sortBy('outbound_answered')" class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 border-l border-gray-200 dark:border-gray-700 cursor-pointer hover:text-gray-700 dark:hover:text-gray-200 select-none whitespace-nowrap" title="{{ __('expert-statistics::pbx.expert_statistics.my_users_tooltip_outbound_answered') }}">{{ __('expert-statistics::pbx.expert_statistics.col_answered') }}{!! $sortIcon('outbound_answered') !!}</th>
                        <th wire:click="sortBy('outbound_talking_duration_total')" class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 cursor-pointer hover:text-gray-700 dark:hover:text-gray-200 select-none whitespace-nowrap" title="{{ __('expert-statistics::pbx.expert_statistics.my_users_tooltip_outbound_duration') }}">{{ __('expert-statistics::pbx.expert_statistics.my_users_subh_duration_total') }}{!! $sortIcon('outbound_talking_duration_total') !!}</th>
                        <th wire:click="sortBy('inbound_answered')" class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 border-l border-gray-200 dark:border-gray-700 cursor-pointer hover:text-gray-700 dark:hover:text-gray-200 select-none whitespace-nowrap" title="{{ __('expert-statistics::pbx.expert_statistics.my_users_tooltip_inbound_answered') }}">{{ __('expert-statistics::pbx.expert_statistics.col_answered') }}{!! $sortIcon('inbound_answered') !!}</th>
                        <th wire:click="sortBy('inbound_talking_duration_total')" class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 cursor-pointer hover:text-gray-700 dark:hover:text-gray-200 select-none whitespace-nowrap" title="{{ __('expert-statistics::pbx.expert_statistics.my_users_tooltip_inbound_duration') }}">{{ __('expert-statistics::pbx.expert_statistics.my_users_subh_duration_total') }}{!! $sortIcon('inbound_talking_duration_total') !!}</th>
                        <th wire:click="sortBy('inbound_unanswered')" class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 cursor-pointer hover:text-gray-700 dark:hover:text-gray-200 select-none whitespace-nowrap" title="{{ __('expert-statistics::pbx.expert_statistics.my_users_tooltip_inbound_unanswered') }}">{{ __('expert-statistics::pbx.expert_statistics.my_users_subh_unanswered') }}{!! $sortIcon('inbound_unanswered') !!}</th>
                        <th wire:click="sortBy('inbound_answered_percentage')" class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 cursor-pointer hover:text-gray-700 dark:hover:text-gray-200 select-none whitespace-nowrap" title="{{ __('expert-statistics::pbx.expert_statistics.my_users_tooltip_inbound_rate') }}">{{ __('expert-statistics::pbx.expert_statistics.col_answer_rate') }}{!! $sortIcon('inbound_answered_percentage') !!}</th>
                        <th wire:click="sortBy('inbound_waiting_duration_avg')" class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 cursor-pointer hover:text-gray-700 dark:hover:text-gray-200 select-none whitespace-nowrap" title="{{ __('expert-statistics::pbx.expert_statistics.my_users_tooltip_inbound_wait_avg') }}">{{ __('expert-statistics::pbx.expert_statistics.my_users_subh_wait_avg') }}{!! $sortIcon('inbound_waiting_duration_avg') !!}</th>
                        <th wire:click="sortBy('internal_received_answered')" class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 border-l border-gray-200 dark:border-gray-700 cursor-pointer hover:text-gray-700 dark:hover:text-gray-200 select-none whitespace-nowrap" title="{{ __('expert-statistics::pbx.expert_statistics.my_users_tooltip_internal_inbound') }}">{{ __('expert-statistics::pbx.expert_statistics.my_users_subh_inbound') }}{!! $sortIcon('internal_received_answered') !!}</th>
                        <th wire:click="sortBy('internal_made_answered')" class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 cursor-pointer hover:text-gray-700 dark:hover:text-gray-200 select-none whitespace-nowrap" title="{{ __('expert-statistics::pbx.expert_statistics.my_users_tooltip_internal_outbound') }}">{{ __('expert-statistics::pbx.expert_statistics.my_users_subh_outbound') }}{!! $sortIcon('internal_made_answered') !!}</th>
                        <th wire:click="sortBy('internal_talking_duration_total')" class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 cursor-pointer hover:text-gray-700 dark:hover:text-gray-200 select-none whitespace-nowrap" title="{{ __('expert-statistics::pbx.expert_statistics.my_users_tooltip_internal_duration') }}">{{ __('expert-statistics::pbx.expert_statistics.my_users_subh_duration_total') }}{!! $sortIcon('internal_talking_duration_total') !!}</th>
                        <th wire:click="sortBy('answered_calls')" class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 border-l border-gray-200 dark:border-gray-700 cursor-pointer hover:text-gray-700 dark:hover:text-gray-200 select-none whitespace-nowrap" title="{{ __('expert-statistics::pbx.expert_statistics.my_users_tooltip_total_answered') }}">{{ __('expert-statistics::pbx.expert_statistics.my_users_subh_count') }}{!! $sortIcon('answered_calls') !!}</th>
                        <th wire:click="sortBy('duration_total')" class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 cursor-pointer hover:text-gray-700 dark:hover:text-gray-200 select-none whitespace-nowrap" title="{{ __('expert-statistics::pbx.expert_statistics.my_users_tooltip_total_duration') }}">{{ __('expert-statistics::pbx.expert_statistics.my_users_subh_duration_total') }}{!! $sortIcon('duration_total') !!}</th>
                        <th wire:click="sortBy('duration_avg')" class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 cursor-pointer hover:text-gray-700 dark:hover:text-gray-200 select-none whitespace-nowrap" title="{{ __('expert-statistics::pbx.expert_statistics.my_users_tooltip_total_duration_avg') }}">{{ __('expert-statistics::pbx.expert_statistics.my_users_subh_duration_avg') }}{!! $sortIcon('duration_avg') !!}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                    @forelse ($displayRows as $row)
                    @php
                        $pct = (float) ($row['inbound_answered_percentage'] ?? 0);
                        $hasInbound = ($row['inbound_answered'] ?? 0) > 0 || ($row['inbound_unanswered'] ?? 0) > 0;
                    @endphp
                    <tr class="{{ $showConsolidated ? 'bg-gray-50 dark:bg-gray-900/30 font-semibold' : 'hover:bg-gray-50 dark:hover:bg-gray-700/30' }} transition-colors">
                        <td class="px-4 py-3 text-gray-800 dark:text-gray-200 whitespace-nowrap font-medium">{{ $row['user'] ?? '—' }}</td>

                        <td class="px-3 py-3 text-right text-blue-600 dark:text-blue-400 border-l border-gray-100 dark:border-gray-700/50">{{ $row['outbound_answered'] ?? 0 }}</td>
                        <td class="px-3 py-3 text-right text-gray-600 dark:text-gray-400">{{ $this->formatDuration($row['outbound_talking_duration_total'] ?? 0) }}</td>

                        <td class="px-3 py-3 text-right text-green-600 dark:text-green-400 border-l border-gray-100 dark:border-gray-700/50">{{ $row['inbound_answered'] ?? 0 }}</td>
                        <td class="px-3 py-3 text-right text-gray-600 dark:text-gray-400">{{ $this->formatDuration($row['inbound_talking_duration_total'] ?? 0) }}</td>
                        <td class="px-3 py-3 text-right text-red-600 dark:text-red-400">{{ $row['inbound_unanswered'] ?? 0 }}</td>
                        <td class="px-3 py-3 text-right">
                            @if ($hasInbound)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                {{ $pct >= 80 ? 'bg-green-100 text-green-700 dark:bg-green-950/40 dark:text-green-400' : ($pct >= 60 ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-950/40 dark:text-yellow-400' : 'bg-red-100 text-red-700 dark:bg-red-950/40 dark:text-red-400') }}">
                                {{ $pct }}%
                            </span>
                            @else
                            <span class="text-gray-400 dark:text-gray-600">—</span>
                            @endif
                        </td>
                        <td class="px-3 py-3 text-right text-gray-600 dark:text-gray-400">{{ $this->formatDuration($row['inbound_waiting_duration_avg'] ?? 0) }}</td>

                        <td class="px-3 py-3 text-right text-purple-600 dark:text-purple-400 border-l border-gray-100 dark:border-gray-700/50">{{ $row['internal_received_answered'] ?? 0 }}</td>
                        <td class="px-3 py-3 text-right text-purple-600 dark:text-purple-400">{{ $row['internal_made_answered'] ?? 0 }}</td>
                        <td class="px-3 py-3 text-right text-gray-600 dark:text-gray-400">{{ $this->formatDuration($row['internal_talking_duration_total'] ?? 0) }}</td>

                        <td class="px-3 py-3 text-right text-gray-800 dark:text-gray-200 font-medium border-l border-gray-100 dark:border-gray-700/50">{{ $row['answered_calls'] ?? 0 }}</td>
                        <td class="px-3 py-3 text-right text-gray-600 dark:text-gray-400">{{ $this->formatDuration($row['duration_total'] ?? 0) }}</td>
                        <td class="px-3 py-3 text-right text-gray-600 dark:text-gray-400">{{ $this->formatDuration($row['duration_avg'] ?? 0) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="14" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">{{ __('expert-statistics::pbx.expert_statistics.no_data') }}</td>
                    </tr>
                    @endforelse
                </tbody>

            </table>

            @elseif ($activeTab === 'status')
            {{-- Status tab --}}
            @php
                $isSingleDay = $startDate && $endDate && $startDate === $endDate;
                $custom1Label = $statusCustom1 ?? __('expert-statistics::pbx.expert_statistics.my_users_status_custom1_fallback');
                $custom2Label = $statusCustom2 ?? __('expert-statistics::pbx.expert_statistics.my_users_status_custom2_fallback');
                $statusRows = $this->getStatusData();
            @endphp
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <th wire:click="sortBy('user')" rowspan="2" class="px-4 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider align-middle cursor-pointer hover:text-gray-700 dark:hover:text-gray-200 select-none whitespace-nowrap">
                            {{ __('expert-statistics::pbx.expert_statistics.my_users_col_user') }}{!! $sortIcon('user') !!}
                        </th>
                        <th colspan="3" class="px-4 py-2 text-center text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wider border-l border-gray-200 dark:border-gray-700">
                            {{ __('expert-statistics::pbx.expert_statistics.my_users_status_group_connexions') }}
                        </th>
                        <th colspan="5" class="px-4 py-2 text-center text-xs font-semibold text-green-600 dark:text-green-400 uppercase tracking-wider border-l border-gray-200 dark:border-gray-700">
                            {{ __('expert-statistics::pbx.expert_statistics.my_users_status_group_distribution') }}
                        </th>
                        <th rowspan="2" class="px-4 py-2 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider align-middle border-l border-gray-200 dark:border-gray-700 cursor-help"
                            title="{{ __('expert-statistics::pbx.expert_statistics.my_users_status_available_out_of_call_tooltip') }}">
                            {{ __('expert-statistics::pbx.expert_statistics.my_users_status_available_out_of_call') }}
                        </th>
                        <th rowspan="2" class="px-4 py-2 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider align-middle border-l border-gray-200 dark:border-gray-700">
                            {{ __('expert-statistics::pbx.expert_statistics.my_users_col_queue_time') }}
                        </th>
                    </tr>
                    <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                        <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 border-l border-gray-200 dark:border-gray-700"
                            title="{{ $isSingleDay ? '' : __('expert-statistics::pbx.expert_statistics.my_users_status_single_day_note') }}">
                            {{ __('expert-statistics::pbx.expert_statistics.my_users_status_login') }}
                            @if (! $isSingleDay)<span class="text-gray-300 dark:text-gray-600 ml-1">*</span>@endif
                        </th>
                        <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400"
                            title="{{ $isSingleDay ? '' : __('expert-statistics::pbx.expert_statistics.my_users_status_single_day_note') }}">
                            {{ __('expert-statistics::pbx.expert_statistics.my_users_status_logout') }}
                            @if (! $isSingleDay)<span class="text-gray-300 dark:text-gray-600 ml-1">*</span>@endif
                        </th>
                        <th wire:click="sortBy('status_total_connection')" class="px-3 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 border-r border-gray-200 dark:border-gray-700 cursor-pointer hover:text-gray-700 dark:hover:text-gray-200 select-none whitespace-nowrap">
                            {{ __('expert-statistics::pbx.expert_statistics.my_users_status_total_connection') }}{!! $sortIcon('status_total_connection') !!}
                        </th>
                        <th wire:click="sortBy('status_available')" class="px-3 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 cursor-pointer hover:text-gray-700 dark:hover:text-gray-200 select-none whitespace-nowrap">
                            {{ __('expert-statistics::pbx.expert_statistics.my_users_status_available') }}{!! $sortIcon('status_available') !!}
                        </th>
                        <th wire:click="sortBy('status_away')" class="px-3 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 cursor-pointer hover:text-gray-700 dark:hover:text-gray-200 select-none whitespace-nowrap">
                            {{ __('expert-statistics::pbx.expert_statistics.my_users_status_away') }}{!! $sortIcon('status_away') !!}
                        </th>
                        <th wire:click="sortBy('status_dnd')" class="px-3 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 cursor-pointer hover:text-gray-700 dark:hover:text-gray-200 select-none whitespace-nowrap">
                            {{ __('expert-statistics::pbx.expert_statistics.my_users_status_dnd') }}{!! $sortIcon('status_dnd') !!}
                        </th>
                        <th wire:click="sortBy('status_custom1')" class="px-3 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 cursor-pointer hover:text-gray-700 dark:hover:text-gray-200 select-none whitespace-nowrap">
                            {{ $custom1Label }}{!! $sortIcon('status_custom1') !!}
                        </th>
                        <th wire:click="sortBy('status_custom2')" class="px-3 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 border-r border-gray-200 dark:border-gray-700 cursor-pointer hover:text-gray-700 dark:hover:text-gray-200 select-none whitespace-nowrap">
                            {{ $custom2Label }}{!! $sortIcon('status_custom2') !!}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                    @forelse ($statusRows as $row)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                        <td class="px-4 py-3 text-gray-800 dark:text-gray-200 whitespace-nowrap font-medium">
                            {{ $row['user'] ?? '—' }}
                        </td>

                        <td class="px-3 py-3 text-center text-gray-600 dark:text-gray-400 border-l border-gray-100 dark:border-gray-700/50 whitespace-nowrap">
                            {{ $isSingleDay ? ($row['login_time'] ?? '—') : '—' }}
                        </td>

                        <td class="px-3 py-3 text-center text-gray-600 dark:text-gray-400 whitespace-nowrap">
                            {{ $isSingleDay ? ($row['logout_time'] ?? '—') : '—' }}
                        </td>

                        <td class="px-3 py-3 text-center text-blue-600 dark:text-blue-400 border-r border-gray-100 dark:border-gray-700/50 whitespace-nowrap">
                            {{ $this->formatMinutes($row['status_summary']['total_active_minutes'] ?? null) }}
                        </td>

                        <td class="px-3 py-3 text-center text-green-600 dark:text-green-400 whitespace-nowrap">
                            {{ $this->formatMinutes($row['status_summary']['available_minutes'] ?? null) }}
                        </td>

                        <td class="px-3 py-3 text-center text-yellow-600 dark:text-yellow-400 whitespace-nowrap">
                            {{ $this->formatMinutes($row['status_summary']['away_minutes'] ?? null) }}
                        </td>

                        <td class="px-3 py-3 text-center text-red-600 dark:text-red-400 whitespace-nowrap">
                            {{ $this->formatMinutes($row['status_summary']['dnd_minutes'] ?? null) }}
                        </td>

                        <td class="px-3 py-3 text-center text-gray-600 dark:text-gray-400 whitespace-nowrap">
                            {{ $this->formatMinutes($row['status_summary']['custom_1_minutes'] ?? null) }}
                        </td>

                        <td class="px-3 py-3 text-center text-gray-600 dark:text-gray-400 border-r border-gray-100 dark:border-gray-700/50 whitespace-nowrap">
                            {{ $this->formatMinutes($row['status_summary']['custom_2_minutes'] ?? null) }}
                        </td>

                        <td class="px-3 py-3 text-center text-gray-600 dark:text-gray-400 whitespace-nowrap">
                            {{ $this->availableOutOfCallFormatted($row) }}
                        </td>

                        <td class="px-3 py-3 text-gray-600 dark:text-gray-400">
                            @php $queueChips = $this->getQueueChips($row); @endphp
                            @if (empty($queueChips))
                                <span class="block text-center">—</span>
                            @else
                                <div class="flex flex-wrap justify-center gap-1.5" title="{{ __('expert-statistics::pbx.expert_statistics.my_users_queue_overlap_note') }}">
                                    @foreach ($queueChips as $chip)
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 dark:bg-gray-700/50 px-2 py-0.5 text-xs whitespace-nowrap">
                                            <span class="w-1.5 h-1.5 rounded-full shrink-0" style="background-color: {{ $chip['color'] }}"></span>
                                            <span class="text-gray-700 dark:text-gray-300">{{ $chip['label'] }}</span>
                                            <span class="text-gray-500 dark:text-gray-400">{{ $chip['duration'] }}</span>
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">
                            {{ __('expert-statistics::pbx.expert_statistics.no_data') }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

            @else
            {{-- Queue connection tab --}}
            @php $queueConnectionRows = $this->getQueueConnectionRows(); @endphp
            <p class="px-4 pt-4 text-xs text-gray-400 dark:text-gray-500">
                {{ __('expert-statistics::pbx.expert_statistics.my_users_queue_overlap_note') }}
            </p>
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">
                            {{ __('expert-statistics::pbx.expert_statistics.my_users_col_user') }}
                        </th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ __('expert-statistics::pbx.expert_statistics.agent_monitoring_queue_connection_col_queue') }}
                        </th>
                        <th class="px-4 py-2 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">
                            {{ __('expert-statistics::pbx.expert_statistics.agent_monitoring_col_duration') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                    @forelse ($queueConnectionRows as $agentRow)
                        @foreach ($agentRow['queues'] as $index => $queue)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                @if ($index === 0)
                                    <td rowspan="{{ count($agentRow['queues']) }}" class="px-4 py-3 text-gray-800 dark:text-gray-200 whitespace-nowrap font-medium align-top border-r border-gray-100 dark:border-gray-700/50">
                                        {{ $agentRow['user'] }}
                                    </td>
                                @endif
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full shrink-0" style="background-color: {{ $queue['color'] }}"></span>
                                        <span class="text-gray-700 dark:text-gray-300">{{ $queue['label'] }}</span>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                    {{ $queue['duration'] }}
                                </td>
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">
                                {{ __('expert-statistics::pbx.expert_statistics.no_data') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            @endif

        </div>
    </div>
    @endif

</div>
