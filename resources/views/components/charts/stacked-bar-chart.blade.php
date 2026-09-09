@props([
    'title',
    'categories',
    'series',
    'chartKey',
    'horizontal' => true,
    'stacked' => true,
    'distributed' => false,
    'colors' => [],
])

@if (! empty($categories))
    <div wire:key="{{ $chartKey }}" class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden">

        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700/50">
            <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $title }}</span>
        </div>

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
                            height: {{ $horizontal ? max(280, count($categories) * 44) : 320 }},
                            stacked: {{ $stacked ? 'true' : 'false' }},
                            toolbar: { show: false },
                            animations: { speed: 300 },
                            background: 'transparent',
                            foreColor: textColor,
                        },
@if ($distributed)
                        colors: {{ Js::from($colors) }},
@endif
                        series: {{ Js::from($series) }},
                        xaxis: {
                            categories: {{ Js::from($categories) }},
                            labels: {
                                style: { colors: textColor, fontSize: '11px' },
@if ($horizontal)
                                formatter: (val) => {
                                    const v = Number(val);
                                    const h = Math.floor(v / 60);
                                    const m = v % 60;
                                    return (h > 0 ? h + 'h ' : '') + m + 'm';
                                },
@endif
                            },
                            axisBorder: { show: false },
                            axisTicks: { show: false },
                        },
                        yaxis: {
                            labels: {
                                style: { colors: textColor, fontSize: '11px' },
@unless ($horizontal)
                                formatter: (val) => {
                                    const v = Number(val);
                                    const h = Math.floor(v / 60);
                                    const m = v % 60;
                                    return (h > 0 ? h + 'h ' : '') + m + 'm';
                                },
@endunless
                            },
                        },
                        grid: {
                            borderColor: gridColor,
                            strokeDashArray: 4,
                        },
                        legend: {
                            show: {{ $distributed ? 'false' : 'true' }},
                            position: 'top',
                            horizontalAlign: 'right',
                            labels: { colors: textColor },
                        },
                        dataLabels: { enabled: false },
                        tooltip: {
                            theme: isDark ? 'dark' : 'light',
                            y: {
                                formatter: (val) => {
                                    const h = Math.floor(val / 60);
                                    const m = val % 60;
                                    return (h > 0 ? h + 'h ' : '') + m + 'm';
                                },
                            },
                        },
                        plotOptions: {
                            bar: {
                                horizontal: {{ $horizontal ? 'true' : 'false' }},
                                borderRadius: 2,
                                distributed: {{ $distributed ? 'true' : 'false' }},
@if ($horizontal)
                                barHeight: '60%',
@else
                                columnWidth: '60%',
@endif
                            },
                        },
                    };

                    new ApexCharts(this.$refs.chart, options).render();
                }
            }"
        >
            <div x-ref="chart"></div>
        </div>
    </div>
@endif
