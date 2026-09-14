<x-expert-statistics::cluster-layout :title="__('expert-statistics::pbx.expert_statistics.my_users_origins_title')">

<div class="space-y-5">

    {{-- Top bar: element selector --}}
    <div class="flex flex-wrap justify-between items-start gap-3">
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

    {{-- View mode toggle: origin family vs top-10 + hide empty --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-3">
            <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                {{ __('expert-statistics::pbx.expert_statistics.kpi_granularity') }}
            </span>
            <div class="flex bg-gray-100 dark:bg-gray-800 rounded-xl p-1 gap-1">
                <button wire:click="{{ ! $isTop10 ? '' : 'toggleTop10' }}" type="button"
                    class="px-3 py-1.5 text-xs font-medium rounded-lg transition-all
                        {{ ! $isTop10
                            ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm'
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200' }}">
                    {{ __('expert-statistics::pbx.expert_statistics.origins_toggle_family') }}
                </button>
                <button wire:click="{{ $isTop10 ? '' : 'toggleTop10' }}" type="button"
                    class="px-3 py-1.5 text-xs font-medium rounded-lg transition-all
                        {{ $isTop10
                            ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm'
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200' }}">
                    {{ __('expert-statistics::pbx.expert_statistics.origins_toggle_top10') }}
                </button>
            </div>
        </div>

        <div class="flex items-center gap-3">
            {{-- Exclude closed hours toggle --}}
            <label class="inline-flex items-center gap-2 cursor-pointer">
                <input type="checkbox" wire:model.live="excludeClosedHours"
                    class="rounded border-gray-300 dark:border-gray-600 text-primary-600" />
                <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('expert-statistics::pbx.expert_statistics.exclude_closed_hours') }}</span>
            </label>

            <button wire:click="toggleHideEmpty" type="button"
                class="flex items-center gap-2 px-3 py-1.5 text-xs font-medium rounded-xl transition-all
                    {{ $hideEmpty
                        ? 'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'
                        : 'bg-primary-50 dark:bg-primary-950/30 text-primary-700 dark:text-primary-300 ring-1 ring-primary-200 dark:ring-primary-800' }}">
                <x-heroicon-o-eye class="w-4 h-4" />
                {{ __('expert-statistics::pbx.expert_statistics.origins_show_empty_users') }}
            </button>
        </div>
    </div>

    @php
        $loadingTargets = 'applyFilters,selectPeriod,updateTimeRange,setCustomRange,applyPbxElementSelection,selectAllPbxElements,clearPbxElements,removePbxElement,toggleTop10,toggleHideEmpty';
    @endphp

    {{-- Loading spinner --}}
    <div wire:loading.flex wire:target="{{ $loadingTargets }}" class="items-center justify-center py-16">
        <div class="w-8 h-8 border-2 border-primary-500 border-t-transparent rounded-full animate-spin"></div>
    </div>

    {{-- Empty state: no element selected --}}
    @if (! $dn)
        <div wire:loading.remove wire:target="{{ $loadingTargets }}"
            class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-12 text-center">
            <x-heroicon-o-arrow-trending-up class="w-12 h-12 mx-auto text-gray-200 dark:text-gray-700 mb-4" />
            <h3 class="text-base font-semibold text-gray-600 dark:text-gray-400 mb-2">
                {{ __('expert-statistics::pbx.expert_statistics.my_users_origins_title') }}
            </h3>
            <p class="text-sm text-gray-400 dark:text-gray-500">{{ __('expert-statistics::pbx.expert_statistics.my_users_empty') }}</p>
        </div>

    @elseif (empty($originData))
        <div wire:loading.remove wire:target="{{ $loadingTargets }}"
            class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-10 text-center">
            <x-heroicon-o-chart-bar class="w-10 h-10 mx-auto text-gray-200 dark:text-gray-700 mb-3" />
            <p class="text-sm text-gray-400 dark:text-gray-500">{{ __('expert-statistics::pbx.expert_statistics.my_users_no_data') }}</p>
        </div>

    @else
        @php
            $displayData = $hideEmpty
                ? array_filter($originData, fn ($row) => collect($row['details'] ?? [])->sum('inbound_calls') > 0)
                : $originData;
        @endphp

        <div wire:loading.remove wire:target="{{ $loadingTargets }}" class="space-y-5">

            {{-- Consolidated donut --}}
            @if (! empty($consolidated))
                <x-expert-statistics::charts.origin-donut
                    :title="__('expert-statistics::pbx.expert_statistics.kpi_consolidated')"
                    :details="$consolidated"
                    :isTop10="$isTop10"
                    :typeLabels="$originTypeLabels"
                    :chartKey="'orig-cons-' . crc32(json_encode($consolidated))"
                />
            @endif

            {{-- Per-DN donut grid --}}
            @if (! empty($displayData))
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    @foreach ($displayData as $row)
                        <x-expert-statistics::charts.origin-donut
                            :title="$row['dst_dn'] ?? '—'"
                            :details="$row['details'] ?? []"
                            :isTop10="$isTop10"
                            :typeLabels="$originTypeLabels"
                            :chartKey="'orig-dn-' . crc32(($row['dst_dn'] ?? '') . json_encode($row['details'] ?? []))"
                        />
                    @endforeach
                </div>
            @endif

        </div>
    @endif

</div>

</x-expert-statistics::cluster-layout>
