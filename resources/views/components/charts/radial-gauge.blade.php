@props([
    'title',
    'icon',
    'iconBgClass',
    'iconColorClass',
    'valueDisplay',
    'subtitleDisplay' => null,
    'seriesColor',
    'gradient' => false,
    'strokeDashArray' => 4,
    'badgeText' => null,
    'badgeColorClasses' => null,
    'percentageJs',
])

@php
    $fill = $gradient
        ? [
            'type' => 'gradient',
            'colors' => [$seriesColor],
            'gradient' => [
                'shade' => 'dark',
                'shadeIntensity' => 0.15,
                'inverseColors' => false,
                'opacityFrom' => 1,
                'opacityTo' => 1,
                'stops' => [0, 50, 65, 91],
            ],
        ]
        : [
            'type' => 'solid',
            'colors' => [$seriesColor],
        ];
@endphp

{{--
    Half-circle radial gauge card, ported from bluerocktelclients'
    resources/views/filament/widgets/pbx/inbound-calls-widget.blade.php (the
    Lost Calls / Avg Wait Time / Short Calls cards) into one reusable
    component. `percentageJs` is the raw body of the Alpine `pct()` method —
    each original card computes its percentage slightly differently (percent
    of total vs. seconds clamped to 100), so this preserves each card's exact
    original math instead of forcing one shared formula.
--}}
<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 px-5 py-5">
    <div class="flex items-start justify-between mb-4">
        <div>
            <div class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1">{{ $title }}</div>
            <div class="text-3xl font-extrabold {{ $iconColorClass }}">{{ $valueDisplay }}</div>
            @if ($subtitleDisplay)
                <div class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ $subtitleDisplay }}</div>
            @endif
        </div>
        <div class="p-2 rounded-xl {{ $iconBgClass }} shrink-0">
            @svg($icon, 'w-5 h-5 '.$iconColorClass)
        </div>
    </div>

    <div class="overflow-hidden flex justify-center -mx-3" style="height:130px">
        <div
            x-data="{
                chart: null,
                pct() {
                    {!! $percentageJs !!}
                },
                init() {
                    const isDark = () => document.documentElement.classList.contains('dark');
                    const trackColor = () => isDark() ? '#374151' : '#f3f4f6';
                    this.chart = new ApexCharts(this.$el.querySelector('[data-chart]'), {
                        chart: { fontFamily: 'inherit', type: 'radialBar', height: 240, offsetY: 0, background: 'transparent' },
                        series: [this.pct()],
                        plotOptions: {
                            radialBar: {
                                startAngle: -90,
                                endAngle: 90,
                                track: { background: trackColor(), strokeWidth: '97%', margin: 5 },
                                dataLabels: { show: false }
                            }
                        },
                        fill: {{ Js::from($fill) }},
                        stroke: { dashArray: {{ (int) $strokeDashArray }} },
                    });
                    this.chart.render();
                    const obs = new MutationObserver(() => this.chart.updateOptions({
                        plotOptions: { radialBar: { track: { background: trackColor() } } }
                    }, false, false, false));
                    obs.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
                    this.$watch('$wire.kpis', () => this.chart.updateSeries([this.pct()]));
                }
            }"
            wire:ignore
            class="shrink-0"
        >
            <div data-chart style="width:300px"></div>
        </div>
    </div>

    @if ($badgeText)
        <div class="inline-flex items-center gap-1.5 mt-3 text-xs font-semibold rounded-full px-2.5 py-1 {{ $badgeColorClasses }}">
            {{ $badgeText }}
        </div>
    @endif
</div>
