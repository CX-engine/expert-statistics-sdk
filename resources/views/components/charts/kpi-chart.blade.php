@props([
    'title',
    'rows',
    'chartKey',
    'showRate' => false,
])

@php
    $categories  = array_column($rows, 'period');
    $answered    = array_column($rows, 'answered');
    $unanswered  = array_column($rows, 'unanswered');
    $avgWaits    = array_column($rows, 'avg_wait');
    $hasWaitData = array_sum($avgWaits) > 0;

    $rates       = array_column($rows, 'rate');
    $hasRateData = $showRate && array_sum($rates) > 0;

    $totalCalls     = array_sum(array_column($rows, 'total'));
    $totalAnswered  = array_sum($answered);
    $totalUnanswered = array_sum($unanswered);
    $answeredRate   = $totalCalls > 0 ? round($totalAnswered / $totalCalls * 100) : 0;
@endphp

<div wire:key="{{ $chartKey }}" class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden">

    {{-- Card header --}}
    <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700/50 flex flex-wrap items-center justify-between gap-3">
        <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $title }}</span>

        <div class="flex flex-wrap items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
            <span>
                <span class="font-medium text-gray-700 dark:text-gray-200">{{ number_format($totalCalls) }}</span>
                {{ __('expert-statistics::pbx.expert_statistics.col_calls') }}
            </span>
            <span class="text-green-600 dark:text-green-400">
                <span class="font-medium">{{ number_format($totalAnswered) }}</span>
                {{ __('expert-statistics::pbx.expert_statistics.col_answered') }}
            </span>
            <span class="text-red-500 dark:text-red-400">
                <span class="font-medium">{{ number_format($totalUnanswered) }}</span>
                {{ __('expert-statistics::pbx.expert_statistics.kpi_unanswered') }}
            </span>
            <span class="{{ $answeredRate >= 80 ? 'text-green-600 dark:text-green-400' : ($answeredRate >= 60 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400') }}">
                <span class="font-medium">{{ $answeredRate }}%</span>
                {{ __('expert-statistics::pbx.expert_statistics.col_answer_rate') }}
            </span>
        </div>
    </div>

    {{-- Stacked bar chart: answered / unanswered --}}
    <div
        class="p-4"
        x-data="{
            init() {
                const isDark = document.documentElement.classList.contains('dark');
                const textColor = isDark ? '#9CA3AF' : '#6B7280';
                const gridColor = isDark ? '#374151' : '#F3F4F6';

                const options = {
                    chart: {
                        type: 'bar',
                        height: 280,
                        stacked: true,
                        toolbar: { show: false },
                        animations: { speed: 300 },
                        background: 'transparent',
                        foreColor: textColor,
                    },
                    series: [
                        {
                            name: {{ Js::from(__('expert-statistics::pbx.expert_statistics.col_answered')) }},
                            data: {{ Js::from($answered) }},
                            color: '#22c55e',
                        },
                        {
                            name: {{ Js::from(__('expert-statistics::pbx.expert_statistics.kpi_unanswered')) }},
                            data: {{ Js::from($unanswered) }},
                            color: '#ef4444',
                        },
                        @if ($hasRateData)
                        {
                            name: {{ Js::from(__('expert-statistics::pbx.expert_statistics.col_answer_rate')) }},
                            data: {{ Js::from($rates) }},
                            type: 'line',
                            color: '#f59e0b',
                        },
                        @endif
                    ],
                    xaxis: {
                        categories: {{ Js::from($categories) }},
                        labels: { style: { colors: textColor, fontSize: '11px' } },
                        axisBorder: { show: false },
                        axisTicks: { show: false },
                    },
                    @if ($hasRateData)
                    yaxis: [
                        {
                            labels: { style: { colors: textColor, fontSize: '11px' } },
                        },
                        {
                            opposite: true,
                            min: 0,
                            max: 100,
                            labels: {
                                style: { colors: textColor, fontSize: '11px' },
                                formatter: (val) => Math.round(val) + '%',
                            },
                        },
                    ],
                    stroke: {
                        width: [0, 0, 2],
                        curve: 'smooth',
                    },
                    @else
                    yaxis: {
                        labels: { style: { colors: textColor, fontSize: '11px' } },
                    },
                    @endif
                    grid: {
                        borderColor: gridColor,
                        strokeDashArray: 4,
                    },
                    legend: {
                        position: 'top',
                        horizontalAlign: 'right',
                        labels: { colors: textColor },
                    },
                    dataLabels: { enabled: false },
                    tooltip: {
                        theme: isDark ? 'dark' : 'light',
                        y: {
                            formatter: (val, opts) => opts.seriesIndex === 2 ? Math.round(val) + '%' : val,
                        },
                    },
                    plotOptions: {
                        bar: {
                            borderRadius: 2,
                            columnWidth: '60%',
                        },
                    },
                };

                new ApexCharts(this.$refs.chart, options).render();
            }
        }"
    >
        <div x-ref="chart"></div>
    </div>

    {{-- Line chart: avg wait time (only when data exists) --}}
    @if ($hasWaitData)
        <div
            class="px-4 pb-4"
            x-data="{
                init() {
                    const isDark = document.documentElement.classList.contains('dark');
                    const textColor = isDark ? '#9CA3AF' : '#6B7280';
                    const gridColor = isDark ? '#374151' : '#F3F4F6';

                    const options = {
                        chart: {
                            type: 'line',
                            height: 160,
                            toolbar: { show: false },
                            animations: { speed: 300 },
                            background: 'transparent',
                            foreColor: textColor,
                        },
                        series: [
                            {
                                name: {{ Js::from(__('expert-statistics::pbx.expert_statistics.kpi_avg_wait')) }},
                                data: {{ Js::from($avgWaits) }},
                                color: '#6366f1',
                            },
                        ],
                        xaxis: {
                            categories: {{ Js::from($categories) }},
                            labels: { style: { colors: textColor, fontSize: '11px' } },
                            axisBorder: { show: false },
                            axisTicks: { show: false },
                        },
                        yaxis: {
                            labels: {
                                style: { colors: textColor, fontSize: '11px' },
                                formatter: (val) => val + 's',
                            },
                        },
                        grid: {
                            borderColor: gridColor,
                            strokeDashArray: 4,
                        },
                        legend: {
                            position: 'top',
                            horizontalAlign: 'right',
                            labels: { colors: textColor },
                        },
                        dataLabels: { enabled: false },
                        stroke: { curve: 'smooth', width: 2 },
                        markers: { size: 3 },
                        tooltip: {
                            theme: isDark ? 'dark' : 'light',
                            y: { formatter: (val) => val + 's' },
                        },
                    };

                    new ApexCharts(this.$refs.waitChart, options).render();
                }
            }"
        >
            <div x-ref="waitChart"></div>
        </div>
    @endif

</div>
