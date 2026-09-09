@props([
'tab',
'showDatePicker',
'startDate',
'endDate',
'selectedPeriod',
'startTime',
'endTime',
])

<div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 px-5 py-4">
    <div class="flex flex-wrap items-center gap-4">

        @if ($tab === 'period')
        {{-- Date range picker --}}
        <div class="relative" x-data="{
                    open: @entangle('showDatePicker'),
                    start: '{{ $startDate }}',
                    end: '{{ $endDate }}'
                }">
            <button @click="open = !open"
                class="inline-flex items-center gap-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 px-3.5 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 transition">
                <x-heroicon-o-calendar class="w-4 h-4 text-gray-400 shrink-0" />
                <span>
                    {{ $startDate ? \Carbon\Carbon::parse($startDate)->format('d/m/Y') : '—' }}
                    &ndash;
                    {{ $endDate ? \Carbon\Carbon::parse($endDate)->format('d/m/Y') : '—' }}
                </span>
                <x-heroicon-m-chevron-down class="w-3 h-3 text-gray-400 shrink-0" />
            </button>

            <div x-show="open" x-transition.opacity @click.away="open = false"
                class="absolute left-0 top-full mt-2 z-50 w-auto bg-white dark:bg-gray-900 rounded-2xl shadow-2xl ring-1 ring-gray-950/10 dark:ring-white/10 p-5">

                <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-3">{{
                    __('expert-statistics::pbx.dashboards.quickPeriods') }}</p>
                <div class="flex flex-wrap gap-1.5 mb-5">
                    @foreach ([
                    'today' => __('expert-statistics::pbx.dashboards.today'),
                    'yesterday' => __('expert-statistics::pbx.dashboards.yesterday'),
                    'currentWeek' => __('expert-statistics::pbx.dashboards.thisWeek'),
                    'lastWeek' => __('expert-statistics::pbx.dashboards.lastWeek'),
                    'currentMonth' => __('expert-statistics::pbx.dashboards.thisMonth'),
                    'lastMonth' => __('expert-statistics::pbx.dashboards.lastMonth'),
                    'last3Months' => __('expert-statistics::pbx.dashboards.last3Months'),
                    'last6Months' => __('expert-statistics::pbx.dashboards.last6Months'),
                    ] as $key => $label)
                    <button wire:click="selectPeriod('{{ $key }}')" @click="open = false"
                        class="rounded-lg px-2.5 py-1 text-xs font-medium transition
                                    {{ $selectedPeriod === $key
                                        ? 'bg-sky-600 text-white'
                                        : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-sky-50 dark:hover:bg-sky-950/50 hover:text-sky-700 dark:hover:text-sky-300' }}">
                        {{ $label }}
                    </button>
                    @endforeach
                </div>

                <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-2">{{
                    __('expert-statistics::pbx.dashboards.customRange') }}</p>
                <div class="flex items-center gap-2 mb-3">
                    <input type="date" x-model="start"
                        class="flex-1 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-sm px-2.5 py-1.5 text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-sky-500 focus:border-sky-500" />
                    <span class="text-gray-300 dark:text-gray-600 shrink-0">&ndash;</span>
                    <input type="date" x-model="end"
                        class="flex-1 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-sm px-2.5 py-1.5 text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-sky-500 focus:border-sky-500" />
                </div>
                <button @click="$wire.setCustomRange(start, end); open = false"
                    class="w-full rounded-lg bg-sky-600 hover:bg-sky-700 text-white text-sm font-medium py-2 transition">
                    {{ __('expert-statistics::pbx.dashboards.applyRange') }}
                </button>
            </div>
        </div>

        {{-- Active period badge --}}
        <span
            class="inline-flex items-center gap-1 rounded-full bg-sky-50 dark:bg-sky-950/50 text-sky-700 dark:text-sky-300 text-xs font-medium px-3 py-1 ring-1 ring-sky-200 dark:ring-sky-800">
            <x-heroicon-m-calendar class="w-3 h-3" />
            {{ match($selectedPeriod) {
            'today' => __('expert-statistics::pbx.dashboards.today'),
            'yesterday' => __('expert-statistics::pbx.dashboards.yesterday'),
            'currentWeek' => __('expert-statistics::pbx.dashboards.thisWeek'),
            'lastWeek' => __('expert-statistics::pbx.dashboards.lastWeek'),
            'currentMonth' => __('expert-statistics::pbx.dashboards.thisMonth'),
            'lastMonth' => __('expert-statistics::pbx.dashboards.lastMonth'),
            'last3Months' => __('expert-statistics::pbx.dashboards.last3Months'),
            'last6Months' => __('expert-statistics::pbx.dashboards.last6Months'),
            default => __('expert-statistics::pbx.dashboards.customRange'),
            } }}
        </span>
        @endif

        {{-- Time range (both tabs) --}}
        <div class="flex items-center gap-2">
            <x-heroicon-o-clock class="w-4 h-4 text-gray-400 shrink-0" />
            <input type="time" wire:model.live="startTime" wire:change="updateTimeRange"
                class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-2.5 py-1.5 text-sm text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-sky-500 focus:border-sky-500" />
            <span class="text-gray-300 dark:text-gray-600">&ndash;</span>
            <input type="time" wire:model.live="endTime" wire:change="updateTimeRange"
                class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-2.5 py-1.5 text-sm text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-sky-500 focus:border-sky-500" />
        </div>

    </div>
</div>