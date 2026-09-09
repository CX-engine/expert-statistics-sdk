@props([
    'title',
    'series',
    'chartKey',
])

@if (! empty($series))
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

                    const options = {
                        chart: {
                            type: 'heatmap',
                            height: {{ max(280, count($series) * 40) }},
                            toolbar: { show: false },
                            animations: { speed: 300 },
                            background: 'transparent',
                            foreColor: textColor,
                        },
                        series: {{ Js::from($series) }},
                        dataLabels: { enabled: false },
                        colors: ['#4f46e5'],
                        plotOptions: {
                            heatmap: {
                                shadeIntensity: 0.65,
                                radius: 3,
                                useFillColorAsStroke: false,
                            },
                        },
                        xaxis: {
                            labels: { style: { colors: textColor, fontSize: '11px' } },
                            axisBorder: { show: false },
                            axisTicks: { show: false },
                        },
                        yaxis: {
                            labels: { style: { colors: textColor, fontSize: '11px' } },
                        },
                        grid: { show: false },
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
                    };

                    new ApexCharts(this.$refs.heatmap, options).render();
                }
            }"
        >
            <div x-ref="heatmap"></div>
        </div>
    </div>
@endif
