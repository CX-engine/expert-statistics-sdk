@props([
    'title',
    'details',
    'isTop10',
    'typeLabels',
    'chartKey',
])

@php
    $colorMap = [
        0   => '#14233C', // Interne
        1   => '#E34B5F', // Externe
        4   => '#97BAA6', // File
        5   => '#EFB0A1', // Messagerie
        6   => '#194E63', // SVI
        14  => '#D9B3A3', // Call Flow
        148 => '#D9B3A3', // Call Flow
        404 => '#D9B3A3', // Call Flow
    ];
    $defaultColor = '#1E7889';

    $series  = [];
    $labels  = [];
    $colors  = [];
    $total   = 0;

    foreach ($details as $detail) {
        $count = (int) ($detail['inbound_calls'] ?? 0);
        if ($count <= 0) {
            continue;
        }

        $type   = $detail['origin_dn_type'] ?? -1;
        // In family mode use typeLabels for the label; in top10 use the DN name
        $label = $isTop10
            ? (filled($detail['origin_display_name'] ?? null) ? $detail['origin_display_name'] : ($detail['origin_dn'] ?? (string) $type))
            : ($typeLabels[$type] ?? (string) $type);

        $color    = $colorMap[$type] ?? $defaultColor;
        $series[] = $count;
        $labels[] = $label;
        $colors[] = $color;
        $total   += $count;
    }
@endphp

@if ($total > 0)
    <div
        wire:key="{{ $chartKey }}"
        class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden"
        x-data="{
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
                                        formatter: () => total.toLocaleString(),
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
                        y: { formatter: (val) => val.toLocaleString() },
                    },
                    stroke: { width: 2, colors: [isDark ? '#1f2937' : '#ffffff'] },
                };

                new ApexCharts(this.$refs.donut, options).render();
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
