<x-pages.index :title="__('expert-statistics::pbx.expert_statistics.my_queues_kpi_title')">

<div class="space-y-5">

    {{-- Top bar: element selector --}}
    <div>
        <x-expert-statistics::element-selector :urlType="$urlType" :pbxElements="$pbxElements"
            :selectedElements="$selectedElements" />
    </div>

    {{-- Filter bar --}}
    <x-expert-statistics::filter-bar :selectedPeriod="$selectedPeriod" :startDate="$startDate"
        :endDate="$endDate" :startTime="$startTime" :endTime="$endTime" :showTimeRange="true" />

    @if ($errorMessage)
        <div class="rounded-xl bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-900 px-4 py-3 text-sm text-red-700 dark:text-red-400">
            {{ $errorMessage }}
        </div>
    @endif

    {{-- Granularity toggle --}}
    <div class="flex flex-wrap items-center gap-3">
        <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
            {{ __('expert-statistics::pbx.expert_statistics.kpi_granularity') }}
        </span>
        <div class="flex bg-gray-100 dark:bg-gray-800 rounded-xl p-1 gap-1">
            @foreach ($granularityOptions as $key => $label)
                <button wire:click="setGranularity('{{ $key }}')" type="button"
                    class="px-3 py-1.5 text-xs font-medium rounded-lg transition-all
                        {{ $granularity === $key
                            ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm'
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200' }}">
                    {{ __($label) }}
                </button>
            @endforeach
        </div>

        {{-- Exclude closed hours toggle --}}
        <label class="inline-flex items-center gap-2 cursor-pointer">
            <input type="checkbox" wire:model.live="excludeClosedHours"
                class="rounded border-gray-300 dark:border-gray-600 text-primary-600" />
            <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('expert-statistics::pbx.expert_statistics.exclude_closed_hours') }}</span>
        </label>
    </div>

    @php
        $loadingTargets = 'applyFilters,selectPeriod,setGranularity,updateTimeRange,setCustomRange,applyPbxElementSelection,selectAllPbxElements,clearPbxElements,removePbxElement';
    @endphp

    {{-- Loading spinner --}}
    <div wire:loading.flex wire:target="{{ $loadingTargets }}" class="items-center justify-center py-16">
        <svg class="w-8 h-8 text-primary-500 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
        </svg>
    </div>

    {{-- Empty state: no element selected --}}
    @if (! $dn)
        <div wire:loading.remove wire:target="{{ $loadingTargets }}"
            class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-12 text-center">
            <x-heroicon-o-presentation-chart-line class="w-12 h-12 mx-auto text-gray-200 dark:text-gray-700 mb-4" />
            <h3 class="text-base font-semibold text-gray-600 dark:text-gray-400 mb-2">
                {{ __('expert-statistics::pbx.expert_statistics.my_queues_kpi_title') }}
            </h3>
            <p class="text-sm text-gray-400 dark:text-gray-500">{{ __('expert-statistics::pbx.expert_statistics.my_queues_empty') }}</p>
        </div>

    @elseif (empty($dnData) && empty($consolidated))
        <div wire:loading.remove wire:target="{{ $loadingTargets }}"
            class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-10 text-center">
            <x-heroicon-o-chart-bar class="w-10 h-10 mx-auto text-gray-200 dark:text-gray-700 mb-3" />
            <p class="text-sm text-gray-400 dark:text-gray-500">{{ __('expert-statistics::pbx.expert_statistics.my_queues_no_data') }}</p>
        </div>

    @else
        <div wire:loading.remove wire:target="{{ $loadingTargets }}" class="space-y-6">

            {{-- Summary cards --}}
            @if (! empty($summary))
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-5">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                            {{ __('expert-statistics::pbx.expert_statistics.col_calls') }}
                        </p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($summary['total']) }}</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-5">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                            {{ __('expert-statistics::pbx.expert_statistics.col_answered') }}
                        </p>
                        <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($summary['answered']) }}</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-5">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                            {{ __('expert-statistics::pbx.expert_statistics.kpi_unanswered') }}
                        </p>
                        <p class="text-2xl font-bold text-red-500 dark:text-red-400">{{ number_format($summary['unanswered']) }}</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-5">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                            {{ __('expert-statistics::pbx.expert_statistics.col_answer_rate') }}
                        </p>
                        @php $rate = $summary['rate'] ?? 0; @endphp
                        <p class="text-2xl font-bold {{ $rate >= 80 ? 'text-green-600 dark:text-green-400' : ($rate >= 60 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400') }}">
                            {{ $rate }}%
                        </p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-5">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                            {{ __('expert-statistics::pbx.expert_statistics.kpi_avg_wait') }}
                        </p>
                        <p class="text-2xl font-bold text-gray-700 dark:text-gray-300">
                            {{ $this->formatDuration($summary['avg_wait'] ?? 0) }}
                        </p>
                    </div>
                </div>
            @endif

            {{-- Consolidated KPI chart --}}
            @if (! empty($consolidated))
                <x-expert-statistics::charts.kpi-chart
                    :title="__('expert-statistics::pbx.expert_statistics.kpi_consolidated')"
                    :rows="$consolidated"
                    :chartKey="'cons-' . $granularity . '-' . crc32(json_encode($consolidated))"
                />
            @endif

            {{-- Per-DN KPI charts --}}
            @foreach ($dnData as $dnItem)
                @if (! empty($dnItem['rows']))
                    <x-expert-statistics::charts.kpi-chart
                        :title="$dnItem['dn']"
                        :rows="$dnItem['rows']"
                        :chartKey="'dn-' . $granularity . '-' . crc32($dnItem['dn']) . '-' . array_sum(array_column($dnItem['rows'], 'total'))"
                    />
                @endif
            @endforeach

        </div>
    @endif

</div>

</x-pages.index>
