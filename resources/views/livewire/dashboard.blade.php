<x-expert-statistics::cluster-layout
    :title="__('expert-statistics::pbx.expert_statistics.nav_dashboard')"
    :subtitle="__('expert-statistics::pbx.expert_statistics.home_feature_dashboard_desc')"
>

<div class="space-y-5">

    {{-- Tab pills --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex bg-gray-100 dark:bg-gray-800 rounded-xl p-1 gap-1">
            <button
                wire:click="switchTab('period')"
                type="button"
                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-150
                    {{ $tab === 'period'
                        ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm'
                        : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200' }}"
            >
                <x-heroicon-m-chart-bar class="w-4 h-4" />
                {{ __('expert-statistics::pbx.dashboards.periodAnalysis') }}
            </button>
            <button
                wire:click="switchTab('trend')"
                type="button"
                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-150
                    {{ $tab === 'trend'
                        ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm'
                        : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200' }}"
            >
                <x-heroicon-m-arrow-trending-up class="w-4 h-4" />
                {{ __('expert-statistics::pbx.dashboards.twelveMonthTrends') }}
            </button>
        </div>

        <x-expert-statistics::report-action-buttons />
    </div>

    {{-- Filter bar (unified — date picker visible only on period tab) --}}
    <x-expert-statistics::dashboard.filter-bar
        :tab="$tab"
        :show-date-picker="$showDatePicker"
        :start-date="$startDate"
        :end-date="$endDate"
        :selected-period="$selectedPeriod"
        :start-time="$startTime"
        :end-time="$endTime"
    />

    @if ($tab === 'period')
    {{--
        Keyed per-tab wrapper: every chart in this component uses wire:ignore
        so Alpine/ApexCharts state survives normal Livewire re-renders (data
        refresh, filter changes). But wire:ignore also makes morphdom treat
        those nodes as opaque during a *structural* change like this @if/@else
        tab swap - without a wire:key forcing Livewire to recognize the whole
        branch as a different element, an ignored chart from the previous tab
        can linger in the DOM instead of being torn down, only fixed by a full
        page reload. Keying the branch itself (not just each chart) guarantees
        a clean unmount/remount on every tab switch.
    --}}
    <div wire:key="dashboard-tab-period">

        {{-- ==================== Total calls ==================== --}}
        <div class="relative mb-5">
            <div wire:loading.flex wire:target="selectPeriod,setCustomRange,updateTimeRange"
                class="absolute inset-0 bg-white/75 dark:bg-gray-800/75 rounded-xl z-20 items-center justify-center backdrop-blur-sm">
                <div class="flex items-center gap-2 bg-white dark:bg-gray-900 rounded-xl shadow-lg ring-1 ring-gray-950/10 dark:ring-white/10 px-4 py-2.5">
                    <x-filament::loading-indicator class="w-5 h-5 text-sky-500" />
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-300">{{ __('expert-statistics::pbx.dashboards.loading') }}</span>
                </div>
            </div>

            @if ($urlType !== null)
                {{-- Hidden when a URL type filter is active --}}
            @elseif (empty($totals))
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-10 text-center">
                    <x-heroicon-o-signal class="w-12 h-12 mx-auto text-gray-200 dark:text-gray-700 mb-3" />
                    <h3 class="font-semibold text-gray-600 dark:text-gray-400 uppercase text-sm tracking-wide mb-2">{{ __('expert-statistics::pbx.dashboards.totalCallsTitle') }}</h3>
                    <p class="text-gray-400 dark:text-gray-500 text-sm">{{ __('expert-statistics::pbx.dashboards.noDataForPeriod') }}</p>
                </div>
            @else
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-4">

                    <div class="flex items-center justify-between mb-5 pb-4 border-b border-gray-100 dark:border-gray-800">
                        <div class="flex items-center gap-2">
                            <div class="w-1 h-5 bg-sky-500 rounded-full"></div>
                            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wide">{{ __('expert-statistics::pbx.dashboards.totalCallsTitle') }}</h2>
                        </div>
                        <button
                            wire:click="toggleDuration"
                            type="button"
                            class="text-xs px-3 py-1 rounded-full border font-medium transition
                                {{ $showDuration
                                    ? 'bg-sky-600 text-white border-sky-600'
                                    : 'bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600' }}"
                        >
                            {{ $showDuration ? __('expert-statistics::pbx.dashboards.callDuration') : __('expert-statistics::pbx.dashboards.callCount') }}
                        </button>
                    </div>

                    <div class="lg:grid lg:grid-cols-2 lg:gap-8 items-center">

                        <div
                            x-data="{
                                chart: null,
                                init() {
                                    const isDark = () => document.documentElement.classList.contains('dark');
                                    this.chart = new ApexCharts(this.$el.querySelector('[data-chart]'), {
                                        chart: { type: 'donut', height: 260, background: 'transparent', foreColor: isDark() ? '#d1d5db' : '#374151' },
                                        theme: { mode: isDark() ? 'dark' : 'light' },
                                        series: this.$wire.totalsChartData.series ?? [],
                                        labels: this.$wire.totalsChartData.labels ?? [],
                                        colors: this.$wire.totalsChartData.colors ?? [],
                                        legend: { position: 'bottom' },
                                        plotOptions: {
                                            pie: {
                                                donut: {
                                                    labels: {
                                                        show: true,
                                                        total: { show: true, label: 'Total', fontSize: '14px' }
                                                    }
                                                }
                                            }
                                        },
                                        dataLabels: { enabled: false },
                                    });
                                    this.chart.render();
                                    const obs = new MutationObserver(() => this.chart.updateOptions({ theme: { mode: isDark() ? 'dark' : 'light' }, chart: { foreColor: isDark() ? '#d1d5db' : '#374151' } }));
                                    obs.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
                                    this.$watch('$wire.totalsChartData', val => {
                                        this.chart.updateOptions({
                                            series: val.series ?? [],
                                            labels: val.labels ?? [],
                                            colors: val.colors ?? [],
                                        }, true, true);
                                    });
                                }
                            }"
                            wire:ignore
                        >
                            <div data-chart></div>
                        </div>

                        <div class="mt-4 lg:mt-0">
                            @php
                                $inboundVal  = $showDuration
                                    ? \CXEngine\ExpertStatistics\Support\PbxDataProcessor::formatSecsToHourMin($totals['inbound']['duration']  ?? 0)
                                    : (string) ($totals['inbound']['calls']  ?? 0);
                                $outboundVal = $showDuration
                                    ? \CXEngine\ExpertStatistics\Support\PbxDataProcessor::formatSecsToHourMin($totals['outbound']['duration'] ?? 0)
                                    : (string) ($totals['outbound']['calls'] ?? 0);
                                $internalVal = $showDuration
                                    ? \CXEngine\ExpertStatistics\Support\PbxDataProcessor::formatSecsToHourMin($totals['internal']['duration'] ?? 0)
                                    : (string) ($totals['internal']['calls'] ?? 0);
                                $totalVal    = $showDuration
                                    ? \CXEngine\ExpertStatistics\Support\PbxDataProcessor::formatSecsToHourMin(
                                        ($totals['inbound']['duration'] ?? 0) + ($totals['outbound']['duration'] ?? 0) + ($totals['internal']['duration'] ?? 0)
                                      )
                                    : (string) (($totals['inbound']['calls'] ?? 0) + ($totals['outbound']['calls'] ?? 0) + ($totals['internal']['calls'] ?? 0));
                            @endphp

                            <div class="grid grid-cols-2 gap-3">
                                @foreach ([
                                    ['label' => __('expert-statistics::pbx.dashboards.totalAppeals'),      'value' => $totalVal,    'color' => 'text-gray-800 dark:text-gray-100',       'bg' => 'bg-gray-50 dark:bg-gray-700/50'],
                                    ['label' => __('expert-statistics::pbx.dashboards.inboundCallsLabel'), 'value' => $inboundVal,  'color' => 'text-sky-600 dark:text-sky-400',         'bg' => 'bg-sky-50 dark:bg-sky-950/30'],
                                    ['label' => __('expert-statistics::pbx.dashboards.outboundCallsLabel'),'value' => $outboundVal, 'color' => 'text-emerald-600 dark:text-emerald-400', 'bg' => 'bg-emerald-50 dark:bg-emerald-950/30'],
                                    ['label' => __('expert-statistics::pbx.dashboards.internalCallsLabel'),'value' => $internalVal, 'color' => 'text-amber-600 dark:text-amber-400',     'bg' => 'bg-amber-50 dark:bg-amber-950/30'],
                                ] as $stat)
                                    <div class="text-center p-4 rounded-xl {{ $stat['bg'] }}">
                                        <div class="text-2xl font-bold {{ $stat['color'] }}">{{ $stat['value'] }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 font-medium">{{ $stat['label'] }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                    </div>

                    <p class="text-xs text-gray-400 dark:text-gray-600 mt-3">{{ __('expert-statistics::pbx.dashboards.externalCallsNote') }}</p>
                </div>
            @endif
        </div>

        {{-- ==================== Inbound calls ==================== --}}
        <div class="relative mb-5">
            <div wire:loading.flex wire:target="selectPeriod,setCustomRange,updateTimeRange"
                class="absolute inset-0 bg-white/75 dark:bg-gray-800/75 rounded-xl z-20 items-center justify-center backdrop-blur-sm">
                <div class="flex items-center gap-2 bg-white dark:bg-gray-900 rounded-xl shadow-lg ring-1 ring-gray-950/10 dark:ring-white/10 px-4 py-2.5">
                    <x-filament::loading-indicator class="w-5 h-5 text-sky-500" />
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-300">{{ __('expert-statistics::pbx.dashboards.loading') }}</span>
                </div>
            </div>

            @php
                $inboundHasData = ! empty($inboundKpis);
                $inboundNeedsSelect = $urlType !== null && empty($selectedElements);
                $inboundTitle = $urlType === 'extension' ? __('expert-statistics::pbx.dashboards.inboundCallsByUsers') : __('expert-statistics::pbx.dashboards.inboundCallsOnQueues');
                $inTotalCalls = (int) ($inboundKpis['totalCalls'] ?? 0);
                $inAnswered = (int) ($inboundKpis['totalAnswered'] ?? 0);
                $inLost = (int) ($inboundKpis['lostCalls'] ?? 0);
                $inTenSec = (int) ($inboundKpis['tenSecondsCalls'] ?? 0);
                $inAvgWait = (float) ($inboundKpis['avgWaitSeconds'] ?? 0);
                $inTalking = (int) ($inboundKpis['totalTalkingSeconds'] ?? 0);
                $inLostPct = $inTotalCalls > 0 ? round($inLost / $inTotalCalls * 100, 1) : 0;
                $inTenSecPct = $inTotalCalls > 0 ? round($inTenSec / $inTotalCalls * 100, 1) : 0;
                $inUnanswered = $inTotalCalls - $inAnswered;
                $inAnswerRate = $inTotalCalls > 0 ? round($inAnswered / $inTotalCalls * 100, 1) : 0;
            @endphp

            @if ($inboundNeedsSelect)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-10 text-center">
                    <x-heroicon-o-signal class="w-12 h-12 mx-auto text-gray-200 dark:text-gray-700 mb-3" />
                    <h3 class="font-semibold text-gray-600 dark:text-gray-400 uppercase text-sm tracking-wide mb-2">{{ $inboundTitle }}</h3>
                    <p class="text-gray-400 dark:text-gray-500 text-sm mb-4">
                        {{ $urlType === 'queue' ? __('expert-statistics::pbx.dashboards.selectQueueAndPeriod') : __('expert-statistics::pbx.dashboards.selectUserAndPeriod') }}
                    </p>
                    <span class="inline-flex items-center gap-2 bg-sky-600 text-white rounded-full px-5 py-2 text-sm font-medium">
                        <x-heroicon-m-cursor-arrow-rays class="w-4 h-4" />
                        {{ $urlType === 'queue' ? __('expert-statistics::pbx.dashboards.selectQueueAndPeriod') : __('expert-statistics::pbx.dashboards.selectUserAndPeriod') }}
                    </span>
                </div>

            @elseif (! $inboundHasData)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-10 text-center">
                    <x-heroicon-o-signal class="w-12 h-12 mx-auto text-gray-200 dark:text-gray-700 mb-3" />
                    <h3 class="font-semibold text-gray-600 dark:text-gray-400 uppercase text-sm tracking-wide mb-2">{{ $inboundTitle }}</h3>
                    <p class="text-gray-400 dark:text-gray-500 text-sm">{{ __('expert-statistics::pbx.dashboards.noDataForPeriod') }}</p>
                </div>

            @else
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-4">

                    <div class="flex items-center justify-between mb-5 pb-4 border-b border-gray-100 dark:border-gray-800">
                        <div class="flex items-center gap-2">
                            <div class="w-1 h-5 bg-sky-500 rounded-full"></div>
                            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wide">{{ $inboundTitle }}</h2>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">

                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-4">
                            <div class="flex items-start justify-between mb-4">
                                <div>
                                    <div class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1">
                                        {{ __('expert-statistics::pbx.dashboards.lostCallsLabel') }}
                                    </div>
                                    <div class="text-3xl font-extrabold text-red-600 dark:text-red-400">{{ $inLostPct }}%</div>
                                    <div class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ __('expert-statistics::pbx.dashboards.outOfNCalls', ['n' => $inTotalCalls]) }}</div>
                                </div>
                                <div class="p-2 rounded-xl bg-red-50 dark:bg-red-950/40 shrink-0">
                                    <x-heroicon-o-phone-arrow-down-left class="w-5 h-5 text-red-500 dark:text-red-400" />
                                </div>
                            </div>
                            <div class="overflow-hidden flex justify-center -mx-3" style="height:130px">
                                <div x-data="{
                                            chart: null,
                                            pct() {
                                                const k = this.$wire.inboundKpis;
                                                const t = parseInt(k.totalCalls ?? 0);
                                                const l = parseInt(k.lostCalls ?? 0);
                                                return t > 0 ? parseFloat((l / t * 100).toFixed(1)) : 0;
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
                                                    fill: { type: 'solid', colors: ['#DC143C'] },
                                                    stroke: { dashArray: 4 },
                                                });
                                                this.chart.render();
                                                const obs = new MutationObserver(() => this.chart.updateOptions({
                                                    plotOptions: { radialBar: { track: { background: trackColor() } } }
                                                }, false, false, false));
                                                obs.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
                                                this.$watch('$wire.inboundKpis', () => this.chart.updateSeries([this.pct()]));
                                            }
                                        }" wire:ignore class="shrink-0">
                                    <div data-chart style="width:300px"></div>
                                </div>
                            </div>
                            <div class="inline-flex items-center gap-1.5 mt-3 text-xs font-semibold rounded-full px-2.5 py-1 bg-red-50 dark:bg-red-950/30 text-red-600 dark:text-red-400">
                                {{ $inLost }} {{ __('expert-statistics::pbx.dashboards.lostCallsLabel') }}
                                @if ($urlType)
                                    &middot; {{ __('expert-statistics::pbx.dashboards.dissuadedCallsNote') }}
                                @endif
                            </div>
                        </div>

                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-4">
                            <div class="flex items-start justify-between mb-4">
                                <div>
                                    <div class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1">
                                        {{ __('expert-statistics::pbx.dashboards.avgAnswerTimeLabel') }}
                                    </div>
                                    <div class="text-3xl font-extrabold text-blue-600 dark:text-blue-400">
                                        {{ (int) $inAvgWait }}<span class="text-lg font-semibold ml-1 text-blue-400 dark:text-blue-500">sec</span>
                                    </div>
                                    <div class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ __('expert-statistics::pbx.dashboards.avgWaitTime') }}</div>
                                </div>
                                <div class="p-2 rounded-xl bg-blue-50 dark:bg-blue-950/40 shrink-0">
                                    <x-heroicon-o-clock class="w-5 h-5 text-blue-500 dark:text-blue-400" />
                                </div>
                            </div>
                            <div class="overflow-hidden flex justify-center -mx-3" style="height:130px">
                                <div x-data="{
                                            chart: null,
                                            gaugeVal() {
                                                return Math.min(100, Math.round(parseFloat(this.$wire.inboundKpis.avgWaitSeconds ?? 0)));
                                            },
                                            init() {
                                                const isDark = () => document.documentElement.classList.contains('dark');
                                                const trackColor = () => isDark() ? '#374151' : '#f3f4f6';
                                                this.chart = new ApexCharts(this.$el.querySelector('[data-chart]'), {
                                                    chart: { fontFamily: 'inherit', type: 'radialBar', height: 240, offsetY: 0, background: 'transparent' },
                                                    series: [this.gaugeVal()],
                                                    plotOptions: {
                                                        radialBar: {
                                                            startAngle: -90,
                                                            endAngle: 90,
                                                            track: { background: trackColor(), strokeWidth: '97%', margin: 5 },
                                                            dataLabels: { show: false }
                                                        }
                                                    },
                                                    fill: {
                                                        type: 'gradient',
                                                        colors: ['#2563eb'],
                                                        gradient: {
                                                            shade: 'dark', shadeIntensity: 0.15, inverseColors: false,
                                                            opacityFrom: 1, opacityTo: 1, stops: [0, 50, 65, 91]
                                                        }
                                                    },
                                                    stroke: { dashArray: 2 },
                                                });
                                                this.chart.render();
                                                const obs = new MutationObserver(() => this.chart.updateOptions({
                                                    plotOptions: { radialBar: { track: { background: trackColor() } } }
                                                }, false, false, false));
                                                obs.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
                                                this.$watch('$wire.inboundKpis', () => this.chart.updateSeries([this.gaugeVal()]));
                                            }
                                        }" wire:ignore class="shrink-0">
                                    <div data-chart style="width:300px"></div>
                                </div>
                            </div>
                            <div class="inline-flex items-center gap-1.5 mt-3 text-xs font-semibold rounded-full px-2.5 py-1 bg-blue-50 dark:bg-blue-950/30 text-blue-600 dark:text-blue-400">
                                {{ __('expert-statistics::pbx.dashboards.avgWaitTime') }}
                                @if (! $urlType)
                                    &middot; {{ __('expert-statistics::pbx.dashboards.avgWaitTimeIncludingPreAnswer') }}
                                @endif
                            </div>
                        </div>

                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-4">
                            <div class="flex items-start justify-between mb-4">
                                <div>
                                    <div class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1">
                                        {{ __('expert-statistics::pbx.dashboards.shortCallsLabel') }}
                                    </div>
                                    <div class="text-3xl font-extrabold text-orange-600 dark:text-orange-400">{{ $inTenSecPct }}%</div>
                                    <div class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ __('expert-statistics::pbx.dashboards.outOfNCalls', ['n' => $inTotalCalls]) }}</div>
                                </div>
                                <div class="p-2 rounded-xl bg-orange-50 dark:bg-orange-950/40 shrink-0">
                                    <x-heroicon-o-bolt class="w-5 h-5 text-orange-500 dark:text-orange-400" />
                                </div>
                            </div>
                            <div class="overflow-hidden flex justify-center -mx-3" style="height:130px">
                                <div x-data="{
                                            chart: null,
                                            pct() {
                                                const k = this.$wire.inboundKpis;
                                                const t = parseInt(k.totalCalls ?? 0);
                                                const s = parseInt(k.tenSecondsCalls ?? 0);
                                                return t > 0 ? parseFloat((s / t * 100).toFixed(1)) : 0;
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
                                                    fill: {
                                                        type: 'gradient',
                                                        colors: ['#ea580c'],
                                                        gradient: {
                                                            shade: 'dark', shadeIntensity: 0.15, inverseColors: false,
                                                            opacityFrom: 1, opacityTo: 1, stops: [0, 50, 65, 91]
                                                        }
                                                    },
                                                    stroke: { dashArray: 6 },
                                                });
                                                this.chart.render();
                                                const obs = new MutationObserver(() => this.chart.updateOptions({
                                                    plotOptions: { radialBar: { track: { background: trackColor() } } }
                                                }, false, false, false));
                                                obs.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
                                                this.$watch('$wire.inboundKpis', () => this.chart.updateSeries([this.pct()]));
                                            }
                                        }" wire:ignore class="shrink-0">
                                    <div data-chart style="width:300px"></div>
                                </div>
                            </div>
                            <div class="inline-flex items-center gap-1.5 mt-3 text-xs font-semibold rounded-full px-2.5 py-1 bg-orange-50 dark:bg-orange-950/30 text-orange-600 dark:text-orange-400">
                                {{ $inTenSec }} {{ __('expert-statistics::pbx.dashboards.shortCallsShort') }}
                            </div>
                        </div>

                    </div>

                    <div class="grid grid-cols-3 gap-3 mb-5 text-center">
                        <div class="rounded-xl bg-green-50 dark:bg-green-950/30 px-3 py-3">
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 font-medium">{{ __('expert-statistics::pbx.dashboards.answered') }}</div>
                            <div class="flex justify-center items-center gap-1">
                                <div class="text-lg font-semibold text-green-600 dark:text-green-400">{{ $inAnswered }}</div>
                                <div class="text-lg font-bold text-green-600 dark:text-green-400">({{ $inAnswerRate }}%)</div>
                            </div>
                        </div>
                        <div class="rounded-xl bg-red-50 dark:bg-red-950/30 px-3 py-3">
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 font-medium">{{ __('expert-statistics::pbx.dashboards.unanswered') }}</div>
                            <div class="flex justify-center items-center gap-1">
                                <div class="text-lg font-semibold text-red-500 dark:text-red-400">{{ $inUnanswered }}</div>
                                <div class="text-lg font-bold text-red-500 dark:text-red-400">({{ round(100 - $inAnswerRate, 1) }}%)</div>
                            </div>
                        </div>
                        <div class="rounded-xl bg-gray-50 dark:bg-gray-700/50 px-3 py-3">
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 font-medium">{{ __('expert-statistics::pbx.dashboards.totalAppeals') }}</div>
                            <div class="text-lg font-semibold text-gray-700 dark:text-gray-200">{{ $inTotalCalls }}</div>
                        </div>
                    </div>

                    <div class="mb-5">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                                {{ $inboundChartView === 'hour' ? __('expert-statistics::pbx.dashboards.viewByHour') : __('expert-statistics::pbx.dashboards.viewByDay') }}
                            </h3>
                            <div class="flex gap-1">
                                <button wire:click="setInboundChartView('hour')" type="button"
                                    class="px-3 py-1 text-xs rounded-lg font-medium transition
                                            {{ $inboundChartView === 'hour' ? 'bg-sky-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                                    {{ __('expert-statistics::pbx.dashboards.viewByHour') }}
                                </button>
                                <button wire:click="setInboundChartView('day')" type="button"
                                    class="px-3 py-1 text-xs rounded-lg font-medium transition
                                            {{ $inboundChartView === 'day' ? 'bg-sky-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                                    {{ __('expert-statistics::pbx.dashboards.viewByDay') }}
                                </button>
                            </div>
                        </div>

                        <div x-data="{
                                    chart: null,
                                    currentData() {
                                        return this.$wire.inboundChartView === 'hour' ? this.$wire.inboundChartByHour : this.$wire.inboundChartByDay;
                                    },
                                    init() {
                                        const isDark = () => document.documentElement.classList.contains('dark');
                                        const d = this.currentData();
                                        this.chart = new ApexCharts(this.$el.querySelector('[data-chart]'), {
                                            chart: { type: 'bar', height: 280, stacked: true, toolbar: { show: false }, background: 'transparent', foreColor: isDark() ? '#d1d5db' : '#374151' },
                                            theme: { mode: isDark() ? 'dark' : 'light' },
                                            series: d.series ?? [],
                                            xaxis: { categories: d.categories ?? [] },
                                            colors: ['#22c55e', '#ef4444'],
                                            dataLabels: { enabled: false },
                                            legend: { position: 'top' },
                                            yaxis: { title: { text: '{{ __('expert-statistics::pbx.dashboards.calls') }}' } },
                                            plotOptions: { bar: { columnWidth: '70%' } },
                                        });
                                        this.chart.render();
                                        const obs = new MutationObserver(() => this.chart.updateOptions({ theme: { mode: isDark() ? 'dark' : 'light' }, chart: { foreColor: isDark() ? '#d1d5db' : '#374151' } }));
                                        obs.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
                                        const update = () => {
                                            const d = this.currentData();
                                            this.chart.updateOptions({
                                                series: d.series ?? [],
                                                xaxis: { categories: d.categories ?? [] },
                                            }, false, true);
                                        };
                                        this.$watch('$wire.inboundChartView', update);
                                        this.$watch('$wire.inboundChartByHour', () => { if (this.$wire.inboundChartView === 'hour') update(); });
                                        this.$watch('$wire.inboundChartByDay',  () => { if (this.$wire.inboundChartView === 'day')  update(); });
                                    }
                                }" wire:ignore>
                            <div data-chart></div>
                        </div>
                    </div>

                    @php
                        $inInsights = [];
                        $inStartHour = (int) substr($startTime, 0, 2);
                        $inEndHour = (int) substr($endTime, 0, 2);

                        if ($inTalking > 0) {
                            $inAvgTalkPerCall = $inTotalCalls > 0 ? (int) ($inTalking / $inTotalCalls) : 0;
                            $inInsights[] = __('expert-statistics::pbx.dashboards.totalInboundDurationPrefix')
                                . ' ' . \CXEngine\ExpertStatistics\Support\PbxDataProcessor::formatSecsToHourMin($inTalking)
                                . '. ' . __('expert-statistics::pbx.dashboards.avgCallDurationConnector')
                                . ' ' . \CXEngine\ExpertStatistics\Support\PbxDataProcessor::formatSecsToMinSec($inAvgTalkPerCall) . '.';
                        }
                        if ($inboundKpis['worstRateHour'] ?? null) {
                            $inWorstH = (int) substr($inboundKpis['worstRateHour'], 0, 2);
                            $inWorstLost = round(100 - ($inboundKpis['worstAnsweredRate'] ?? 0), 1);
                            $inInsights[] = __('expert-statistics::pbx.dashboards.lowestAnswerRateSlot')
                                . ' ' . __('expert-statistics::pbx.dashboards.between')
                                . ' ' . $inWorstH . 'h ' . __('expert-statistics::pbx.common.and')
                                . ' ' . ($inWorstH + 1) . 'h, '
                                . __('expert-statistics::pbx.dashboards.pctCallsNotAnswered', ['pct' => $inWorstLost]);
                        }
                        if ($inboundKpis['bestRateHour'] ?? null) {
                            $inBestH = (int) substr($inboundKpis['bestRateHour'], 0, 2);
                            $inInsights[] = __('expert-statistics::pbx.dashboards.bestAnswerRateSlot')
                                . ' ' . __('expert-statistics::pbx.dashboards.between')
                                . ' ' . $inBestH . 'h ' . __('expert-statistics::pbx.common.and')
                                . ' ' . ($inBestH + 1) . 'h, '
                                . __('expert-statistics::pbx.dashboards.pctCallsAnswered', ['pct' => $inboundKpis['bestAnsweredRate'] ?? 0]);
                        }
                        if ($inboundKpis['mostCallsHour'] ?? null) {
                            $inBusiestH = (int) substr($inboundKpis['mostCallsHour'], 0, 2);
                            $inInsights[] = __('expert-statistics::pbx.dashboards.mostCallsSlot')
                                . ' ' . __('expert-statistics::pbx.dashboards.between')
                                . ' ' . $inBusiestH . 'h ' . __('expert-statistics::pbx.common.and')
                                . ' ' . ($inBusiestH + 1) . 'h, '
                                . __('expert-statistics::pbx.dashboards.callsReceivedCount', ['n' => $inboundKpis['mostCalls']]);
                        }
                    @endphp
                    @if (count($inInsights))
                        <div class="grid grid-cols-4 gap-3 mb-2">
                            @foreach ($inInsights as $insight)
                                <div class="col-span-1 rounded-xl bg-sky-50 dark:bg-sky-950/30 text-sky-700 dark:text-sky-300 ring-1 ring-sky-200 dark:ring-sky-900 px-3 py-2 text-xs">
                                    {!! $insight !!}
                                </div>
                            @endforeach
                        </div>
                        <div class="text-right text-xs text-gray-400 dark:text-gray-600">
                            {{ __('expert-statistics::pbx.dashboards.timeRangeNote', ['from' => $inStartHour, 'to' => $inEndHour]) }}
                        </div>
                    @endif

                </div>
            @endif
        </div>

        {{-- ==================== Outbound calls ==================== --}}
        <div class="relative mb-5">
            <div wire:loading.flex wire:target="selectPeriod,setCustomRange,updateTimeRange"
                class="absolute inset-0 bg-white/75 dark:bg-gray-800/75 rounded-xl z-20 items-center justify-center backdrop-blur-sm">
                <div class="flex items-center gap-2 bg-white dark:bg-gray-900 rounded-xl shadow-lg ring-1 ring-gray-950/10 dark:ring-white/10 px-4 py-2.5">
                    <x-filament::loading-indicator class="w-5 h-5 text-sky-500" />
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-300">{{ __('expert-statistics::pbx.dashboards.loading') }}</span>
                </div>
            </div>

            @if ($urlType !== null)
                {{-- Hidden when a URL type filter is active --}}
            @elseif (empty($outboundKpis))
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-10 text-center">
                    <x-heroicon-o-signal class="w-12 h-12 mx-auto text-gray-200 dark:text-gray-700 mb-3" />
                    <h3 class="font-semibold text-gray-600 dark:text-gray-400 uppercase text-sm tracking-wide mb-2">{{ __('expert-statistics::pbx.dashboards.externalOutboundCalls') }}</h3>
                    <p class="text-gray-400 dark:text-gray-500 text-sm">{{ __('expert-statistics::pbx.dashboards.noDataForPeriod') }}</p>
                </div>
            @else
                @php
                    $outTotalCalls = (int) ($outboundKpis['totalCalls']    ?? 0);
                    $outAnswered   = (int) ($outboundKpis['totalAnswered'] ?? 0);
                    $outTalking    = (int) ($outboundKpis['totalTalkingSeconds'] ?? 0);
                    $outUnanswered = $outTotalCalls - $outAnswered;
                    $outAnswerRate = $outTotalCalls > 0 ? round($outAnswered / $outTotalCalls * 100, 1) : 0;
                    $outStartHour  = (int) substr($startTime, 0, 2);
                    $outEndHour    = (int) substr($endTime, 0, 2);
                @endphp

                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-4">

                    <div class="flex items-center justify-between mb-5 pb-4 border-b border-gray-100 dark:border-gray-800">
                        <div class="flex items-center gap-2">
                            <div class="w-1 h-5 bg-emerald-500 rounded-full"></div>
                            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wide">{{ __('expert-statistics::pbx.dashboards.externalOutboundCalls') }}</h2>
                        </div>
                        <div class="flex gap-1">
                            <button wire:click="setOutboundChartView('hour')" type="button"
                                class="px-3 py-1 text-xs rounded-lg font-medium transition
                                    {{ $outboundChartView === 'hour' ? 'bg-sky-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                                {{ __('expert-statistics::pbx.dashboards.viewByHour') }}
                            </button>
                            <button wire:click="setOutboundChartView('day')" type="button"
                                class="px-3 py-1 text-xs rounded-lg font-medium transition
                                    {{ $outboundChartView === 'day' ? 'bg-sky-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                                {{ __('expert-statistics::pbx.dashboards.viewByDay') }}
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3 mb-5 text-center">
                        <div class="rounded-xl bg-green-50 dark:bg-green-950/30 px-3 py-3">
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 font-medium">{{ __('expert-statistics::pbx.dashboards.answered') }}</div>
                            <div class="flex justify-center items-center gap-1">
                                <div class="text-lg font-semibold text-green-600 dark:text-green-400">{{ $outAnswered }}</div>
                                <div class="text-lg font-bold text-green-600 dark:text-green-400">({{ $outAnswerRate }}%)</div>
                            </div>
                        </div>
                        <div class="rounded-xl bg-red-50 dark:bg-red-950/30 px-3 py-3">
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 font-medium">{{ __('expert-statistics::pbx.dashboards.unanswered') }}</div>
                            <div class="flex justify-center items-center gap-1">
                                <div class="text-lg font-semibold text-red-500 dark:text-red-400">{{ $outUnanswered }}</div>
                                <div class="text-lg font-bold text-red-500 dark:text-red-400">({{ round(100 - $outAnswerRate, 1) }}%)</div>
                            </div>
                        </div>
                        <div class="rounded-xl bg-gray-50 dark:bg-gray-700/50 px-3 py-3">
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 font-medium">{{ __('expert-statistics::pbx.dashboards.totalAppeals') }}</div>
                            <div class="text-lg font-semibold text-gray-700 dark:text-gray-200">{{ $outTotalCalls }}</div>
                        </div>
                    </div>

                    <div class="mb-5"
                        x-data="{
                            chart: null,
                            currentData() {
                                return this.$wire.outboundChartView === 'hour' ? this.$wire.outboundChartByHour : this.$wire.outboundChartByDay;
                            },
                            init() {
                                const isDark = () => document.documentElement.classList.contains('dark');
                                const d = this.currentData();
                                this.chart = new ApexCharts(this.$el.querySelector('[data-chart]'), {
                                    chart: { type: 'bar', height: 280, stacked: true, toolbar: { show: false }, background: 'transparent', foreColor: isDark() ? '#d1d5db' : '#374151' },
                                    theme: { mode: isDark() ? 'dark' : 'light' },
                                    series: d.series ?? [],
                                    xaxis: { categories: d.categories ?? [] },
                                    colors: ['#22c55e', '#ef4444'],
                                    dataLabels: { enabled: false },
                                    legend: { position: 'top' },
                                    yaxis: { title: { text: '{{ __('expert-statistics::pbx.dashboards.calls') }}' } },
                                    plotOptions: { bar: { columnWidth: '70%' } },
                                });
                                this.chart.render();
                                const obs = new MutationObserver(() => this.chart.updateOptions({ theme: { mode: isDark() ? 'dark' : 'light' }, chart: { foreColor: isDark() ? '#d1d5db' : '#374151' } }));
                                obs.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
                                const update = () => {
                                    const d = this.currentData();
                                    this.chart.updateOptions({
                                        series: d.series ?? [],
                                        xaxis: { categories: d.categories ?? [] },
                                    }, false, true);
                                };
                                this.$watch('$wire.outboundChartView', update);
                                this.$watch('$wire.outboundChartByHour', () => { if (this.$wire.outboundChartView === 'hour') update(); });
                                this.$watch('$wire.outboundChartByDay',  () => { if (this.$wire.outboundChartView === 'day')  update(); });
                            }
                        }"
                        wire:ignore
                    >
                        <div data-chart></div>
                    </div>

                    @php
                        $outInsights = [];

                        if ($outTalking > 0) {
                            $outAvgTalkPerCall = $outTotalCalls > 0 ? (int) ($outTalking / $outTotalCalls) : 0;
                            $outInsights[] = __('expert-statistics::pbx.dashboards.totalOutboundDurationPrefix')
                                . ' ' . \CXEngine\ExpertStatistics\Support\PbxDataProcessor::formatSecsToHourMin($outTalking)
                                . '. ' . __('expert-statistics::pbx.dashboards.avgCallDurationConnector')
                                . ' ' . \CXEngine\ExpertStatistics\Support\PbxDataProcessor::formatSecsToMinSec($outAvgTalkPerCall) . '.';
                        }
                        if ($outboundKpis['worstRateHour'] ?? null) {
                            $outWorstH    = (int) substr($outboundKpis['worstRateHour'], 0, 2);
                            $outWorstLost = round(100 - ($outboundKpis['worstAnsweredRate'] ?? 0), 1);
                            $outInsights[] = __('expert-statistics::pbx.dashboards.lowestAnswerRateSlot')
                                . ' ' . __('expert-statistics::pbx.dashboards.between')
                                . ' ' . $outWorstH . 'h ' . __('expert-statistics::pbx.common.and')
                                . ' ' . ($outWorstH + 1) . 'h, '
                                . __('expert-statistics::pbx.dashboards.pctCallsNotAnswered', ['pct' => $outWorstLost]);
                        }
                        if ($outboundKpis['bestRateHour'] ?? null) {
                            $outBestH = (int) substr($outboundKpis['bestRateHour'], 0, 2);
                            $outInsights[] = __('expert-statistics::pbx.dashboards.bestAnswerRateSlot')
                                . ' ' . __('expert-statistics::pbx.dashboards.between')
                                . ' ' . $outBestH . 'h ' . __('expert-statistics::pbx.common.and')
                                . ' ' . ($outBestH + 1) . 'h, '
                                . __('expert-statistics::pbx.dashboards.pctCallsAnswered', ['pct' => $outboundKpis['bestAnsweredRate'] ?? 0]);
                        }
                        if ($outboundKpis['mostCallsHour'] ?? null) {
                            $outBusiestH = (int) substr($outboundKpis['mostCallsHour'], 0, 2);
                            $outInsights[] = __('expert-statistics::pbx.dashboards.mostCallsSlot')
                                . ' ' . __('expert-statistics::pbx.dashboards.between')
                                . ' ' . $outBusiestH . 'h ' . __('expert-statistics::pbx.common.and')
                                . ' ' . ($outBusiestH + 1) . 'h, '
                                . __('expert-statistics::pbx.dashboards.callsEmittedCount', ['n' => $outboundKpis['mostCalls']]);
                        }
                    @endphp
                    @if (count($outInsights))
                        <div class="grid grid-cols-4 gap-3 mb-2">
                            @foreach ($outInsights as $insight)
                                <div class="rounded-xl bg-emerald-50 dark:bg-emerald-950/30 text-emerald-700 dark:text-emerald-300 ring-1 ring-emerald-200 dark:ring-emerald-900 px-3 py-2 text-xs">
                                    {!! $insight !!}
                                </div>
                            @endforeach
                        </div>
                        <div class="text-right text-xs text-gray-400 dark:text-gray-600">
                            {{ __('expert-statistics::pbx.dashboards.timeRangeNote', ['from' => $outStartHour, 'to' => $outEndHour]) }}
                        </div>
                    @endif

                </div>
            @endif
        </div>

        {{-- ==================== Top 10 users ==================== --}}
        <div class="relative mb-5">
            <div wire:loading.flex wire:target="selectPeriod,setCustomRange,updateTimeRange"
                class="absolute inset-0 bg-white/75 dark:bg-gray-800/75 rounded-xl z-20 items-center justify-center backdrop-blur-sm">
                <div class="flex items-center gap-2 bg-white dark:bg-gray-900 rounded-xl shadow-lg ring-1 ring-gray-950/10 dark:ring-white/10 px-4 py-2.5">
                    <x-filament::loading-indicator class="w-5 h-5 text-sky-500" />
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-300">{{ __('expert-statistics::pbx.dashboards.loading') }}</span>
                </div>
            </div>

            @if ($urlType === 'queue')
                {{-- Hidden when queue filter is active --}}
            @elseif (empty($topUsersChartData))
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-10 text-center">
                    <x-heroicon-o-signal class="w-12 h-12 mx-auto text-gray-200 dark:text-gray-700 mb-3" />
                    <h3 class="font-semibold text-gray-600 dark:text-gray-400 uppercase text-sm tracking-wide mb-2">{{ __('expert-statistics::pbx.dashboards.top10Users') }}</h3>
                    <p class="text-gray-400 dark:text-gray-500 text-sm">{{ __('expert-statistics::pbx.dashboards.noDataForPeriod') }}</p>
                </div>
            @else
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-4">

                    <div class="flex items-center gap-2 mb-5 pb-4 border-b border-gray-100 dark:border-gray-800">
                        <div class="w-1 h-5 bg-amber-500 rounded-full"></div>
                        <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wide">{{ __('expert-statistics::pbx.dashboards.top10Users') }}</h2>
                    </div>

                    <div
                        x-data="{
                            chart: null,
                            init() {
                                const isDark = () => document.documentElement.classList.contains('dark');
                                const d = this.$wire.topUsersChartData;
                                const h = Math.max(200, (d.labels?.length ?? 0) * 32 + 80);
                                this.chart = new ApexCharts(this.$el.querySelector('[data-chart]'), {
                                    chart: { type: 'bar', height: h, stacked: true, toolbar: { show: false }, background: 'transparent', foreColor: isDark() ? '#d1d5db' : '#374151' },
                                    theme: { mode: isDark() ? 'dark' : 'light' },
                                    plotOptions: { bar: { horizontal: true, barHeight: '60%' } },
                                    series: d.series ?? [],
                                    xaxis: { categories: d.labels ?? [], title: { text: '{{ __('expert-statistics::pbx.dashboards.calls') }}' } },
                                    colors: ['#0EA5E9', '#00E396', '#eab308'],
                                    dataLabels: { enabled: false },
                                    legend: { position: 'top' },
                                    grid: { xaxis: { lines: { show: true } }, yaxis: { lines: { show: false } } },
                                });
                                this.chart.render();
                                const obs = new MutationObserver(() => this.chart.updateOptions({ theme: { mode: isDark() ? 'dark' : 'light' }, chart: { foreColor: isDark() ? '#d1d5db' : '#374151' } }));
                                obs.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
                                this.$watch('$wire.topUsersChartData', val => {
                                    const newH = Math.max(200, (val.labels?.length ?? 0) * 32 + 80);
                                    this.chart.updateOptions({
                                        chart: { height: newH },
                                        series: val.series ?? [],
                                        xaxis: { categories: val.labels ?? [], title: { text: '{{ __('expert-statistics::pbx.dashboards.calls') }}' } },
                                    }, false, true);
                                });
                            }
                        }"
                        wire:ignore
                    >
                        <div data-chart></div>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-3">
                        <div class="inline-flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                            <span class="w-2 h-2 rounded-full bg-sky-500 shrink-0"></span>
                            <span><strong>{{ __('expert-statistics::pbx.dashboards.inboundCallsLabel') }}:</strong> {{ __('expert-statistics::pbx.dashboards.top10Note1') }}</span>
                        </div>
                        <div class="inline-flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 shrink-0"></span>
                            <span><strong>{{ __('expert-statistics::pbx.dashboards.outboundCallsLabel') }}:</strong> {{ __('expert-statistics::pbx.dashboards.top10Note2') }}</span>
                        </div>
                        <div class="inline-flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                            <span class="w-2 h-2 rounded-full bg-amber-400 shrink-0"></span>
                            <span><strong>{{ __('expert-statistics::pbx.dashboards.internalCallsLabel') }}:</strong> {{ __('expert-statistics::pbx.dashboards.top10Note3') }}</span>
                        </div>
                    </div>

                </div>
            @endif
        </div>

    </div>
    @else
    <div wire:key="dashboard-tab-trend">

        {{-- ==================== Trend KPI cards ==================== --}}
        <div class="relative mb-5">
            <div wire:loading.flex wire:target="updateTimeRange"
                class="absolute inset-0 bg-white/75 dark:bg-gray-800/75 rounded-xl z-20 items-center justify-center backdrop-blur-sm">
                <div class="flex items-center gap-2 bg-white dark:bg-gray-900 rounded-xl shadow-lg ring-1 ring-gray-950/10 dark:ring-white/10 px-4 py-2.5">
                    <x-filament::loading-indicator class="w-5 h-5 text-sky-500" />
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-300">{{ __('expert-statistics::pbx.dashboards.loading') }}</span>
                </div>
            </div>

            @if (empty($trends))
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-10 text-center">
                    <x-heroicon-o-signal class="w-12 h-12 mx-auto text-gray-200 dark:text-gray-700 mb-3" />
                    <h3 class="font-semibold text-gray-500 dark:text-gray-400 uppercase text-sm tracking-wide mb-1">{{ __('expert-statistics::pbx.dashboards.twelveMonthTrends') }}</h3>
                    <p class="text-gray-400 dark:text-gray-500 text-sm">{{ __('expert-statistics::pbx.dashboards.noTrendData') }}</p>
                </div>
            @else
                @php
                    $trendCurrentMonth = $trends['currentMonthName'] ?? '';
                    $trendLastMonth    = $trends['lastMonthName']    ?? '';
                    $trendCurrentYear  = $trends['currentYear']      ?? '';
                    $trendTotalCalls   = $trends['totalCalls']       ?? 0;
                    $trendCarCalls     = $trends['carCalls']         ?? 0;
                    $trendAvgWait      = $trends['averageWaitTime']  ?? 0;
                    $trendTotalTrend   = $trends['totalTrend']       ?? 0;
                    $trendCarTrend     = $trends['carTrend']         ?? 0;
                    $trendWaitTrend    = $trends['waitTimeTrend']    ?? 0;
                @endphp

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-4">
                        <div class="flex items-start justify-between mb-3">
                            <div>
                                <div class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1">{{ __('expert-statistics::pbx.dashboards.totalCallsTitle') }}</div>
                                <div class="text-3xl font-extrabold text-teal-600 dark:text-teal-400">{{ number_format($trendTotalCalls) }}</div>
                                <div class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ $trendCurrentMonth }} {{ $trendCurrentYear }}</div>
                            </div>
                            <div class="p-2 rounded-xl bg-teal-50 dark:bg-teal-950/40">
                                <x-heroicon-o-phone class="w-5 h-5 text-teal-600 dark:text-teal-400" />
                            </div>
                        </div>
                        @if ($trendTotalTrend == 0)
                            <div class="text-xs text-gray-400 dark:text-gray-500">
                                {{ __('expert-statistics::pbx.dashboards.noChange') }} — {{ __('expert-statistics::pbx.dashboards.comparedTo', ['month' => $trendLastMonth]) }}
                            </div>
                        @else
                            <div class="inline-flex items-center gap-1 text-xs font-semibold rounded-full px-2 py-1
                                {{ $trendTotalTrend > 0
                                    ? 'bg-green-50 dark:bg-green-950/30 text-green-600 dark:text-green-400'
                                    : 'bg-red-50 dark:bg-red-950/30 text-red-500 dark:text-red-400' }}">
                                @if ($trendTotalTrend > 0)
                                    <x-heroicon-m-arrow-trending-up class="w-3 h-3" />
                                @else
                                    <x-heroicon-m-arrow-trending-down class="w-3 h-3" />
                                @endif
                                {{ abs($trendTotalTrend) }}% {{ __('expert-statistics::pbx.dashboards.comparedTo', ['month' => $trendLastMonth]) }}
                            </div>
                        @endif
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-4">
                        <div class="flex items-start justify-between mb-3">
                            <div>
                                <div class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1">{{ __('expert-statistics::pbx.dashboards.answerRate') }}</div>
                                <div class="text-3xl font-extrabold text-lime-600 dark:text-lime-400">{{ $trendCarCalls }}%</div>
                                <div class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ $trendCurrentMonth }} {{ $trendCurrentYear }}</div>
                            </div>
                            <div class="p-2 rounded-xl bg-lime-50 dark:bg-lime-950/40">
                                <x-heroicon-o-check-circle class="w-5 h-5 text-lime-600 dark:text-lime-400" />
                            </div>
                        </div>
                        @if ($trendCarTrend == 0)
                            <div class="text-xs text-gray-400 dark:text-gray-500">
                                {{ __('expert-statistics::pbx.dashboards.noChange') }} — {{ __('expert-statistics::pbx.dashboards.comparedTo', ['month' => $trendLastMonth]) }}
                            </div>
                        @else
                            <div class="inline-flex items-center gap-1 text-xs font-semibold rounded-full px-2 py-1
                                {{ $trendCarTrend > 0
                                    ? 'bg-green-50 dark:bg-green-950/30 text-green-600 dark:text-green-400'
                                    : 'bg-red-50 dark:bg-red-950/30 text-red-500 dark:text-red-400' }}">
                                @if ($trendCarTrend > 0)
                                    <x-heroicon-m-arrow-trending-up class="w-3 h-3" />
                                @else
                                    <x-heroicon-m-arrow-trending-down class="w-3 h-3" />
                                @endif
                                {{ abs($trendCarTrend) }}% {{ __('expert-statistics::pbx.dashboards.comparedTo', ['month' => $trendLastMonth]) }}
                            </div>
                        @endif
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-4">
                        <div class="flex items-start justify-between mb-3">
                            <div>
                                <div class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1">{{ __('expert-statistics::pbx.dashboards.avgWaitTime') }}</div>
                                <div class="text-3xl font-extrabold text-blue-600 dark:text-blue-400">
                                    {{ \CXEngine\ExpertStatistics\Support\PbxDataProcessor::formatSecsToMinSec((int) $trendAvgWait) }}
                                </div>
                                <div class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ $trendCurrentMonth }} {{ $trendCurrentYear }}</div>
                            </div>
                            <div class="p-2 rounded-xl bg-blue-50 dark:bg-blue-950/40">
                                <x-heroicon-o-clock class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                            </div>
                        </div>
                        @if ($trendWaitTrend == 0)
                            <div class="text-xs text-gray-400 dark:text-gray-500">
                                {{ __('expert-statistics::pbx.dashboards.noChange') }} — {{ __('expert-statistics::pbx.dashboards.comparedTo', ['month' => $trendLastMonth]) }}
                            </div>
                        @else
                            {{-- Positive wait trend = worse (red); negative = better (green) --}}
                            <div class="inline-flex items-center gap-1 text-xs font-semibold rounded-full px-2 py-1
                                {{ $trendWaitTrend > 0
                                    ? 'bg-red-50 dark:bg-red-950/30 text-red-500 dark:text-red-400'
                                    : 'bg-green-50 dark:bg-green-950/30 text-green-600 dark:text-green-400' }}">
                                @if ($trendWaitTrend > 0)
                                    <x-heroicon-m-arrow-trending-up class="w-3 h-3" />
                                @else
                                    <x-heroicon-m-arrow-trending-down class="w-3 h-3" />
                                @endif
                                {{ abs($trendWaitTrend) }}% {{ __('expert-statistics::pbx.dashboards.comparedTo', ['month' => $trendLastMonth]) }}
                            </div>
                        @endif
                    </div>

                </div>
            @endif
        </div>

        {{-- ==================== Trend charts ==================== --}}
        <div class="relative space-y-5">
            <div wire:loading.flex wire:target="updateTimeRange"
                class="absolute inset-0 bg-white/75 dark:bg-gray-800/75 rounded-xl z-20 items-center justify-center backdrop-blur-sm">
                <div class="flex items-center gap-2 bg-white dark:bg-gray-900 rounded-xl shadow-lg ring-1 ring-gray-950/10 dark:ring-white/10 px-4 py-2.5">
                    <x-filament::loading-indicator class="w-5 h-5 text-sky-500" />
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-300">{{ __('expert-statistics::pbx.dashboards.loading') }}</span>
                </div>
            </div>

            @if (empty($answeredChart))
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-10 text-center">
                    <x-heroicon-o-signal class="w-12 h-12 mx-auto text-gray-200 dark:text-gray-700 mb-3" />
                    <h3 class="font-semibold text-gray-500 dark:text-gray-400 uppercase text-sm tracking-wide mb-1">{{ __('expert-statistics::pbx.dashboards.twelveMonthTrends') }}</h3>
                    <p class="text-gray-400 dark:text-gray-500 text-sm">{{ __('expert-statistics::pbx.dashboards.noTrendData') }}</p>
                </div>
            @else

                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-4">
                    <div class="flex items-center gap-2 mb-5 pb-4 border-b border-gray-100 dark:border-gray-800">
                        <div class="w-1 h-5 bg-teal-500 rounded-full"></div>
                        <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wide">{{ __('expert-statistics::pbx.dashboards.answeredCallsTrend') }}</h2>
                    </div>

                    <div
                        x-data="{
                            chart: null,
                            init() {
                                const isDark = () => document.documentElement.classList.contains('dark');
                                const d = this.$wire.answeredChart;
                                this.chart = new ApexCharts(this.$el.querySelector('[data-chart]'), {
                                    chart: { type: 'line', height: 350, toolbar: { show: false }, background: 'transparent', foreColor: isDark() ? '#d1d5db' : '#374151' },
                                    theme: { mode: isDark() ? 'dark' : 'light' },
                                    series: d.series ?? [],
                                    xaxis: {
                                        categories: d.labels ?? [],
                                        labels: { rotate: -65, rotateAlways: true, showDuplicates: false },
                                    },
                                    yaxis: [
                                        { title: { text: '{{ __('expert-statistics::pbx.dashboards.calls') }}' }, seriesName: 'Total Calls' },
                                        { seriesName: 'Answered Calls', show: false },
                                        {
                                            opposite: true,
                                            title: { text: '{{ __('expert-statistics::pbx.dashboards.answerRate') }} (%)' },
                                            min: 0, max: 100,
                                            labels: { formatter: v => v + '%' },
                                            seriesName: 'Answer Rate %',
                                        },
                                    ],
                                    colors: ['#0d9488', '#84cc16', '#0d9488'],
                                    stroke: { width: [0, 3, 3], curve: 'smooth', dashArray: [0, 0, 5] },
                                    plotOptions: { bar: { columnWidth: '50%' } },
                                    tooltip: { shared: true },
                                    legend: { position: 'top' },
                                    dataLabels: { enabled: false },
                                });
                                this.chart.render();
                                const obs = new MutationObserver(() => this.chart.updateOptions({ theme: { mode: isDark() ? 'dark' : 'light' }, chart: { foreColor: isDark() ? '#d1d5db' : '#374151' } }));
                                obs.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
                                this.$watch('$wire.answeredChart', val => {
                                    this.chart.updateOptions({
                                        series: val.series ?? [],
                                        xaxis: { categories: val.labels ?? [] },
                                    }, false, true);
                                });
                            }
                        }"
                        wire:ignore
                    >
                        <div data-chart></div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-4">
                    <div class="flex items-center gap-2 mb-5 pb-4 border-b border-gray-100 dark:border-gray-800">
                        <div class="w-1 h-5 bg-blue-500 rounded-full"></div>
                        <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wide">{{ __('expert-statistics::pbx.dashboards.avgWaitTimeTrend') }}</h2>
                    </div>

                    <div
                        x-data="{
                            chart: null,
                            fmt(v) {
                                const m = Math.floor(v / 60);
                                const s = Math.round(v % 60);
                                return m + ':' + String(s).padStart(2, '0');
                            },
                            init() {
                                const isDark = () => document.documentElement.classList.contains('dark');
                                const d = this.$wire.waitTimeChart;
                                const self = this;
                                this.chart = new ApexCharts(this.$el.querySelector('[data-chart]'), {
                                    chart: { type: 'line', height: 300, toolbar: { show: false }, background: 'transparent', foreColor: isDark() ? '#d1d5db' : '#374151' },
                                    theme: { mode: isDark() ? 'dark' : 'light' },
                                    series: d.series ?? [],
                                    xaxis: {
                                        categories: d.labels ?? [],
                                        labels: { rotate: -65, rotateAlways: true, showDuplicates: false },
                                    },
                                    yaxis: { labels: { formatter: v => self.fmt(v) } },
                                    colors: ['#2563eb'],
                                    stroke: { width: 3, curve: 'smooth' },
                                    legend: { show: false },
                                    dataLabels: { enabled: false },
                                    tooltip: { y: { formatter: v => self.fmt(v) } },
                                });
                                this.chart.render();
                                const obs = new MutationObserver(() => this.chart.updateOptions({ theme: { mode: isDark() ? 'dark' : 'light' }, chart: { foreColor: isDark() ? '#d1d5db' : '#374151' } }));
                                obs.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
                                this.$watch('$wire.waitTimeChart', val => {
                                    this.chart.updateOptions({
                                        series: val.series ?? [],
                                        xaxis: { categories: val.labels ?? [] },
                                    }, false, true);
                                });
                            }
                        }"
                        wire:ignore
                    >
                        <div data-chart></div>
                    </div>
                </div>

            @endif
        </div>

    </div>
    @endif

    <livewire:expert-statistics.reports.share-report-modal />

</div>

</x-expert-statistics::cluster-layout>
