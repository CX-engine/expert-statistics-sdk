<x-expert-statistics::cluster-layout :title="__('expert-statistics::pbx.expert_statistics.my_queues_dashboard_title')">

<div class="space-y-5">

    {{-- Header row: element selector --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <x-expert-statistics::element-selector :urlType="$urlType" :pbxElements="$pbxElements"
            :selectedElements="$selectedElements" />
    </div>

    {{-- Filter bar --}}
    <x-expert-statistics::filter-bar :selectedPeriod="$selectedPeriod" :startDate="$startDate" :endDate="$endDate"
        :startTime="$startTime" :endTime="$endTime" :showTimeRange="true" />

    {{-- Unique calls + exclude closed hours toggles --}}
    <div class="flex flex-wrap items-center gap-4">
        <x-expert-statistics::unique-calls-toggle
            :enabled="true"
            :checked="$uniqueCalls"
            wireModel="uniqueCalls"
        />

        <label class="inline-flex items-center gap-2 cursor-pointer">
            <input type="checkbox" wire:model.live="excludeClosedHours"
                class="rounded border-gray-300 dark:border-gray-600 text-primary-600" />
            <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('expert-statistics::pbx.expert_statistics.exclude_closed_hours') }}</span>
        </label>
    </div>

    @php
        $loadingTargets = 'selectPeriod,setCustomRange,updateTimeRange,applyPbxElementSelection,selectAllPbxElements,clearPbxElements,removePbxElement';
    @endphp

    {{-- Loading spinner --}}
    <div wire:loading.flex wire:target="{{ $loadingTargets }}" class="items-center justify-center py-16">
        <svg class="w-8 h-8 text-primary-500 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
        </svg>
    </div>

    <div wire:loading.remove wire:target="{{ $loadingTargets }}" class="space-y-5">

        {{-- Empty state: no queue selected --}}
        @if (empty($selectedElements))
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-10 text-center">
                <x-heroicon-o-signal class="w-12 h-12 mx-auto text-gray-200 dark:text-gray-700 mb-3" />
                <h3 class="font-semibold text-gray-600 dark:text-gray-400 uppercase text-sm tracking-wide mb-2">
                    {{ __('expert-statistics::pbx.dashboards.inboundCallsOnQueues') }}
                </h3>
                <p class="text-gray-400 dark:text-gray-500 text-sm">{{ __('expert-statistics::pbx.dashboards.selectQueueAndPeriod') }}</p>
            </div>

        @elseif (empty($kpis))
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-10 text-center">
                <x-heroicon-o-signal class="w-12 h-12 mx-auto text-gray-200 dark:text-gray-700 mb-3" />
                <h3 class="font-semibold text-gray-600 dark:text-gray-400 uppercase text-sm tracking-wide mb-2">
                    {{ __('expert-statistics::pbx.dashboards.inboundCallsOnQueues') }}
                </h3>
                <p class="text-gray-400 dark:text-gray-500 text-sm">{{ __('expert-statistics::pbx.dashboards.noDataForPeriod') }}</p>
            </div>

        @else
            @php
                $totalCalls = (int) ($kpis['totalCalls'] ?? 0);
                $answered = (int) ($kpis['totalAnswered'] ?? 0);
                $lost = (int) ($kpis['lostCalls'] ?? 0);
                $tenSec = (int) ($kpis['tenSecondsCalls'] ?? 0);
                $avgWait = (float) ($kpis['avgWaitSeconds'] ?? 0);
                $talking = (int) ($kpis['totalTalkingSeconds'] ?? 0);
                $unanswered = max(0, $totalCalls - $answered);
                $answerRate = $totalCalls > 0 ? round($answered / $totalCalls * 100, 1) : 0;
                $lostPct = $totalCalls > 0 ? round($lost / $totalCalls * 100, 1) : 0;
                $tenSecPct = $totalCalls > 0 ? round($tenSec / $totalCalls * 100, 1) : 0;

                $insights = [];
                $startHour = (int) substr($startTime, 0, 2);
                $endHour = (int) substr($endTime, 0, 2);

                if ($talking > 0) {
                    $avgTalkPerCall = $totalCalls > 0 ? (int) ($talking / $totalCalls) : 0;
                    $insights[] = __('expert-statistics::pbx.dashboards.totalInboundDurationPrefix')
                        .' '.\CXEngine\ExpertStatistics\Support\PbxDataProcessor::formatSecsToHourMin($talking)
                        .'. '.__('expert-statistics::pbx.dashboards.avgCallDurationConnector')
                        .' '.\CXEngine\ExpertStatistics\Support\PbxDataProcessor::formatSecsToMinSec($avgTalkPerCall).'.';
                }
                if ($kpis['worstRateHour'] ?? null) {
                    $worstH = (int) substr($kpis['worstRateHour'], 0, 2);
                    $worstLost = round(100 - ($kpis['worstAnsweredRate'] ?? 0), 1);
                    $insights[] = __('expert-statistics::pbx.dashboards.lowestAnswerRateSlot')
                        .' '.__('expert-statistics::pbx.dashboards.between')
                        .' '.$worstH.'h '.__('expert-statistics::pbx.common.and')
                        .' '.($worstH + 1).'h, '
                        .__('expert-statistics::pbx.dashboards.pctCallsNotAnswered', ['pct' => $worstLost]);
                }
                if ($kpis['bestRateHour'] ?? null) {
                    $bestH = (int) substr($kpis['bestRateHour'], 0, 2);
                    $insights[] = __('expert-statistics::pbx.dashboards.bestAnswerRateSlot')
                        .' '.__('expert-statistics::pbx.dashboards.between')
                        .' '.$bestH.'h '.__('expert-statistics::pbx.common.and')
                        .' '.($bestH + 1).'h, '
                        .__('expert-statistics::pbx.dashboards.pctCallsAnswered', ['pct' => $kpis['bestAnsweredRate'] ?? 0]);
                }
                if ($kpis['mostCallsHour'] ?? null) {
                    $busiestH = (int) substr($kpis['mostCallsHour'], 0, 2);
                    $insights[] = __('expert-statistics::pbx.dashboards.mostCallsSlot')
                        .' '.__('expert-statistics::pbx.dashboards.between')
                        .' '.$busiestH.'h '.__('expert-statistics::pbx.common.and')
                        .' '.($busiestH + 1).'h, '
                        .__('expert-statistics::pbx.dashboards.callsReceivedCount', ['n' => $kpis['mostCalls'] ?? 0]);
                }
            @endphp

            {{-- Radial gauge cards --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <x-expert-statistics::charts.radial-gauge
                    :title="__('expert-statistics::pbx.dashboards.lostCallsLabel')"
                    icon="heroicon-o-phone-arrow-down-left"
                    iconBgClass="bg-red-50 dark:bg-red-950/40"
                    iconColorClass="text-red-600 dark:text-red-400"
                    :valueDisplay="$lostPct . '%'"
                    :subtitleDisplay="__('expert-statistics::pbx.dashboards.outOfNCalls', ['n' => $totalCalls])"
                    seriesColor="#DC143C"
                    :gradient="false"
                    :strokeDashArray="4"
                    :badgeText="$lost . ' ' . __('expert-statistics::pbx.dashboards.lostCallsLabel')"
                    badgeColorClasses="bg-red-50 dark:bg-red-950/30 text-red-600 dark:text-red-400"
                    percentageJs="const k = this.$wire.kpis; const t = parseInt(k.totalCalls ?? 0); const l = parseInt(k.lostCalls ?? 0); return t > 0 ? parseFloat((l / t * 100).toFixed(1)) : 0;"
                />

                <x-expert-statistics::charts.radial-gauge
                    :title="__('expert-statistics::pbx.dashboards.avgAnswerTimeLabel')"
                    icon="heroicon-o-clock"
                    iconBgClass="bg-blue-50 dark:bg-blue-950/40"
                    iconColorClass="text-blue-600 dark:text-blue-400"
                    :valueDisplay="(int) $avgWait . 'sec'"
                    :subtitleDisplay="__('expert-statistics::pbx.dashboards.avgWaitTime')"
                    seriesColor="#2563eb"
                    :gradient="true"
                    :strokeDashArray="2"
                    :badgeText="__('expert-statistics::pbx.dashboards.avgWaitTime')"
                    badgeColorClasses="bg-blue-50 dark:bg-blue-950/30 text-blue-600 dark:text-blue-400"
                    percentageJs="return Math.min(100, Math.round(parseFloat(this.$wire.kpis.avgWaitSeconds ?? 0)));"
                />

                <x-expert-statistics::charts.radial-gauge
                    :title="__('expert-statistics::pbx.dashboards.shortCallsLabel')"
                    icon="heroicon-o-bolt"
                    iconBgClass="bg-orange-50 dark:bg-orange-950/40"
                    iconColorClass="text-orange-600 dark:text-orange-400"
                    :valueDisplay="$tenSecPct . '%'"
                    :subtitleDisplay="__('expert-statistics::pbx.dashboards.outOfNCalls', ['n' => $totalCalls])"
                    seriesColor="#ea580c"
                    :gradient="true"
                    :strokeDashArray="6"
                    :badgeText="$tenSec . ' ' . __('expert-statistics::pbx.dashboards.shortCallsShort')"
                    badgeColorClasses="bg-orange-50 dark:bg-orange-950/30 text-orange-600 dark:text-orange-400"
                    percentageJs="const k = this.$wire.kpis; const t = parseInt(k.totalCalls ?? 0); const s = parseInt(k.tenSecondsCalls ?? 0); return t > 0 ? parseFloat((s / t * 100).toFixed(1)) : 0;"
                />
            </div>

            {{-- KPI summary row --}}
            <div class="grid grid-cols-3 gap-3 text-center">
                <div class="rounded-xl bg-green-50 dark:bg-green-950/30 px-3 py-3">
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 font-medium">{{ __('expert-statistics::pbx.dashboards.answered') }}</div>
                    <div class="flex justify-center items-center gap-1">
                        <div class="text-lg font-semibold text-green-600 dark:text-green-400">{{ $answered }}</div>
                        <div class="text-lg font-bold text-green-600 dark:text-green-400">({{ $answerRate }}%)</div>
                    </div>
                </div>
                <div class="rounded-xl bg-red-50 dark:bg-red-950/30 px-3 py-3">
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 font-medium">{{ __('expert-statistics::pbx.dashboards.unanswered') }}</div>
                    <div class="flex justify-center items-center gap-1">
                        <div class="text-lg font-semibold text-red-500 dark:text-red-400">{{ $unanswered }}</div>
                        <div class="text-lg font-bold text-red-500 dark:text-red-400">({{ round(100 - $answerRate, 1) }}%)</div>
                    </div>
                </div>
                <div class="rounded-xl bg-gray-50 dark:bg-gray-700/50 px-3 py-3">
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 font-medium">{{ __('expert-statistics::pbx.dashboards.totalAppeals') }}</div>
                    <div class="text-lg font-semibold text-gray-700 dark:text-gray-200">{{ $totalCalls }}</div>
                </div>
            </div>

            {{-- Insight chips --}}
            @if (count($insights))
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    @foreach ($insights as $insight)
                        <div class="col-span-1 rounded-xl bg-sky-50 dark:bg-sky-950/30 text-sky-700 dark:text-sky-300 ring-1 ring-sky-200 dark:ring-sky-900 px-3 py-2 text-xs">
                            {!! $insight !!}
                        </div>
                    @endforeach
                </div>
                <div class="text-right text-xs text-gray-400 dark:text-gray-600">
                    {{ __('expert-statistics::pbx.dashboards.timeRangeNote', ['from' => $startHour, 'to' => $endHour]) }}
                </div>
            @endif

            {{-- Hour / day view toggle --}}
            <div class="flex items-center justify-between">
                <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                    {{ $chartView === 'hour' ? __('expert-statistics::pbx.dashboards.viewByHour') : __('expert-statistics::pbx.dashboards.viewByDay') }}
                </h3>
                <div class="flex gap-1">
                    <button wire:click="setChartView('hour')" type="button"
                        class="px-3 py-1 text-xs rounded-lg font-medium transition
                            {{ $chartView === 'hour' ? 'bg-primary-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                        {{ __('expert-statistics::pbx.dashboards.viewByHour') }}
                    </button>
                    <button wire:click="setChartView('day')" type="button"
                        class="px-3 py-1 text-xs rounded-lg font-medium transition
                            {{ $chartView === 'day' ? 'bg-primary-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                        {{ __('expert-statistics::pbx.dashboards.viewByDay') }}
                    </button>
                </div>
            </div>

            @php $inboundRows = $this->getInboundRows(); @endphp
            <x-expert-statistics::charts.kpi-chart
                :title="__('expert-statistics::pbx.dashboards.inboundCallsOnQueues')"
                :rows="$inboundRows"
                :chartKey="'inbound-' . $chartView . '-' . crc32(json_encode($inboundRows))"
            />
        @endif

    </div>

</div>

</x-expert-statistics::cluster-layout>
