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

            {{-- Group by day / total --}}
            <div class="flex bg-gray-100 dark:bg-gray-800 rounded-xl p-1 gap-1">
                <button wire:click="toggleGroupData" type="button" class="px-4 py-2 text-sm font-medium rounded-lg transition-all
                        {{ $groupData
                            ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm'
                            : 'text-gray-500 dark:text-gray-400' }}">
                    {{ __('expert-statistics::pbx.expert_statistics.toggle_period_total') }}
                </button>
                <button wire:click="toggleGroupData" type="button" class="px-4 py-2 text-sm font-medium rounded-lg transition-all
                        {{ ! $groupData
                            ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm'
                            : 'text-gray-500 dark:text-gray-400' }}">
                    {{ __('expert-statistics::pbx.expert_statistics.toggle_day_by_day') }}
                </button>
            </div>
        </div>
    @endif

    @php
        $loadingTargets = 'applyFilters,selectPeriod,toggleGroupData,setCustomRange,updateTimeRange,applyPbxElementSelection,selectAllPbxElements,clearPbxElements,removePbxElement';
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
            class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-12 text-center">
            <x-heroicon-o-queue-list class="w-12 h-12 mx-auto text-gray-200 dark:text-gray-700 mb-4" />
            <h3 class="text-base font-semibold text-gray-600 dark:text-gray-400 mb-2">{{ __('expert-statistics::pbx.expert_statistics.my_queues_title') }}</h3>
            <p class="text-sm text-gray-400 dark:text-gray-500">{{ __('expert-statistics::pbx.expert_statistics.my_queues_empty') }}</p>
        </div>

    @elseif (empty($queueData) && empty($extensionData))
        <div wire:loading.remove wire:target="{{ $loadingTargets }}"
            class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-10 text-center">
            <x-heroicon-o-chart-bar class="w-10 h-10 mx-auto text-gray-200 dark:text-gray-700 mb-3" />
            <p class="text-sm text-gray-400 dark:text-gray-500">{{ __('expert-statistics::pbx.expert_statistics.my_queues_no_data') }}</p>
        </div>

    @else
        @php
            $rows = $this->getDisplayData();

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
        <div wire:loading.remove wire:target="{{ $loadingTargets }}"
            class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">

                        {{-- Column headers with sort --}}
                        <tr>
                            @if (! $groupData)
                                <th wire:click="sortBy('day')"
                                    class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-200 select-none whitespace-nowrap">
                                    {{ __('expert-statistics::pbx.expert_statistics.col_date') }}{!! $sortIcon('day') !!}
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
                                    <span>Déclinés</span>
                                    <span>Abandonnés</span>
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

                            <th wire:click="sortBy('talking_duration')"
                                class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-200 select-none whitespace-nowrap">
                                {{ __('expert-statistics::pbx.expert_statistics.col_talk_duration') }}{!! $sortIcon('talking_duration') !!}
                            </th>

                            <th wire:click="sortBy('waiting_duration')"
                                class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-200 select-none whitespace-nowrap">
                                {{ __('expert-statistics::pbx.expert_statistics.col_wait_duration') }}{!! $sortIcon('waiting_duration') !!}
                            </th>
                        </tr>

                        {{-- Filter row --}}
                        <tr class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
                            @if (! $groupData)<td class="px-2 py-1"></td>@endif

                            {{-- Queue filter --}}
                            <td class="px-2 py-1">
                                <input
                                    wire:model.live.debounce.300ms="filterQueue"
                                    type="text"
                                    placeholder="Filtrer file…"
                                    class="w-full text-xs rounded border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-200 placeholder-gray-400 px-2 py-1 focus:ring-1 focus:ring-primary-500 focus:border-primary-500 focus:outline-none" />
                            </td>

                            {{-- Extension filter --}}
                            @if (! $queuesOnlyStats)
                                <td class="px-2 py-1">
                                    <input
                                        wire:model.live.debounce.300ms="filterExtension"
                                        type="text"
                                        placeholder="Filtrer extension…"
                                        class="w-full text-xs rounded border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-200 placeholder-gray-400 px-2 py-1 focus:ring-1 focus:ring-primary-500 focus:border-primary-500 focus:outline-none" />
                                </td>
                            @endif

                            {{-- Empty cells for remaining columns --}}
                            @php $filterColspan = $queuesOnlyStats ? 8 : 7; @endphp
                            <td colspan="{{ $filterColspan }}" class="px-2 py-1"></td>
                        </tr>

                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                        @forelse ($rows as $row)
                            @php
                                $isQueue = ($row['keyType'] ?? null) == 4;
                                $calls = (int) ($row['calls'] ?? 0);
                                $answered = (int) ($row['answered'] ?? 0);
                                $abandoned = (int) ($row['abandoned'] ?? 0);
                                $preanswerAbandoned = (int) ($row['preanswer_abandoned'] ?? 0);
                                $declined = max(0, $calls - $answered - $abandoned);
                                $divisor = $calls - $preanswerAbandoned;
                                $pct = $divisor > 0 ? ($answered / $divisor) * 100 : 0;
                            @endphp
                            <tr
                                class="{{ $isQueue && ! $queuesOnlyStats ? 'bg-gray-50/50 dark:bg-gray-900/20 font-medium' : '' }} hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                @if (! $groupData)
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                        {{ $this->formatDate($row['day'] ?? '') }}
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
                                    {{ $calls }}
                                </td>
                                <td class="px-4 py-3 text-red-600 dark:text-red-400">
                                    <div class="grid grid-cols-2 gap-2 text-right">
                                        <span>{{ $declined }}</span>
                                        <span>{{ $abandoned }}{{ $preanswerAbandoned > 0 ? ' ('.$preanswerAbandoned.')' : '' }}</span>
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
                                    {{ $answered }}
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
                                </td>
                                <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-400">
                                    {{ $this->formatDuration($row['talking_duration'] ?? 0) }}
                                </td>
                                <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-400">
                                    {{ $this->formatDuration($row['waiting_duration'] ?? 0) }}
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
            </div>
        </div>
    @endif

    <livewire:expert-statistics.reports.share-report-modal />

</div>
