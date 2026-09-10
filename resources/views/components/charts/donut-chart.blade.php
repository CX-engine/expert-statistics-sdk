@props([
    'title',
    'items',
    'chartKey',
])

@php
    $series = [];
    $labels = [];
    $colors = [];
    $total = 0;

    foreach ($items as $item) {
        $minutes = (int) ($item['minutes'] ?? 0);
        if ($minutes <= 0) {
            continue;
        }

        $series[] = $minutes;
        $labels[] = (string) ($item['label'] ?? '');
        $colors[] = (string) ($item['color'] ?? '#9ca3af');
        $total += $minutes;
    }
@endphp

@if ($total > 0)
    <div
        wire:key="{{ $chartKey }}"
        class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden"
        x-data="{
            chart: null,
            init() {
                const isDark = document.documentElement.classList.contains('dark');
                const textColor = isDark ? '#9CA3AF' : '#6B7280';
                const total = {{ $total }};

                const options = {
                    chart: {
                        type: 'donut',
                        height: 280,
                        toolbar: { show: false },
                        animations: { speed: 300 },
                        background: 'transparent',
                    },
                    series: {{ Js::from($series) }},
                    labels: {{ Js::from($labels) }},
                    colors: {{ Js::from($colors) }},
                    legend: {
                        position: 'bottom',
                        labels: { colors: textColor },
                    },
                    dataLabels: {
                        enabled: true,
                        formatter: (val) => Math.round(val) + '%',
                        style: { fontSize: '11px' },
                        dropShadow: { enabled: false },
                    },
                    plotOptions: {
                        pie: {
                            donut: {
                                size: '45%',
                                labels: {
                                    show: true,
                                    total: {
                                        show: true,
                                        label: '',
                                        formatter: () => {
                                            const h = Math.floor(total / 60);
                                            const m = total % 60;
                                            return (h > 0 ? h + 'h ' : '') + m + 'm';
                                        },
                                        color: textColor,
                                        fontSize: '18px',
                                        fontWeight: 700,
                                    },
                                },
                            },
                        },
                    },
                    tooltip: {
                        theme: isDark ? 'dark' : 'light',
                        y: { formatter: (val) => Math.floor(val / 60) + 'h ' + (val % 60) + 'm' },
                    },
                    stroke: { width: 2, colors: [isDark ? '#1f2937' : '#ffffff'] },
                };

                this.chart = new ApexCharts(this.$refs.donut, options);
                this.chart.render().catch(() => {});
            },
            destroy() {
                this.chart?.destroy();
            }
        }"
    >
        {{-- Card header --}}
        <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-700/50">
            <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $title }}</span>
        </div>

        <div class="p-4">
            <div x-ref="donut"></div>
        </div>
    </div>
@endif
