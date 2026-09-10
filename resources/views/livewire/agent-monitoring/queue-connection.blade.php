<div class="space-y-5">

    {{-- Header: agent selector + consolidated toggle --}}
    <div class="flex flex-wrap justify-between items-start gap-3">
        <x-expert-statistics::element-selector :urlType="$urlType" :pbxElements="$pbxElements"
            :selectedElements="$selectedElements" />

        <label class="inline-flex items-center gap-2 cursor-pointer">
            <input
                type="checkbox"
                wire:model.live="showConsolidated"
                class="rounded border-gray-300 dark:border-gray-600 text-primary-600"
            />
            <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('expert-statistics::pbx.expert_statistics.agent_monitoring_consolidated') }}</span>
        </label>
    </div>

    {{-- Filter bar: date range only — the underlying data has no time-of-day granularity --}}
    <x-expert-statistics::filter-bar :selectedPeriod="$selectedPeriod" :startDate="$startDate"
        :endDate="$endDate" :startTime="$startTime" :endTime="$endTime" :showTimeRange="false" />

    @if ($errorMessage)
        <div class="rounded-xl bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-900 px-4 py-3 text-sm text-red-700 dark:text-red-400">
            {{ $errorMessage }}
        </div>
    @endif

    @php
        $loadingTargets = 'applyFilters,selectPeriod,applyPbxElementSelection,selectAllPbxElements,clearPbxElements,removePbxElement';
        $perAgent = $this->getPerAgentBreakdown();
        $aggregateItems = $this->getAggregateByQueue();
        $perAgentSeries = $this->getPerAgentSeries();
        $dailySeries = $this->getDailySeries();
        $dailySeriesPerAgent = $this->getDailySeriesPerAgent();
        $heatmapSeries = $this->getHeatmapSeries();
        $aggregateChart = $this->getAggregateChartData();
    @endphp

    {{-- Loading spinner --}}
    <div wire:loading.flex wire:target="{{ $loadingTargets }}" class="items-center justify-center py-16">
        <svg class="w-8 h-8 text-primary-500 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
        </svg>
    </div>

    {{-- Empty state: no agent selected --}}
    @if (! $dn)
        <div wire:loading.remove wire:target="{{ $loadingTargets }}"
            class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-12 text-center">
            <x-heroicon-o-phone-arrow-up-right class="w-12 h-12 mx-auto text-gray-200 dark:text-gray-700 mb-4" />
            <h3 class="text-base font-semibold text-gray-600 dark:text-gray-400 mb-2">
                {{ __('expert-statistics::pbx.expert_statistics.agent_monitoring_queue_connection_title') }}
            </h3>
            <p class="text-sm text-gray-400 dark:text-gray-500">{{ __('expert-statistics::pbx.expert_statistics.my_users_empty') }}</p>
        </div>

    @elseif (empty($perAgent))
        <div wire:loading.remove wire:target="{{ $loadingTargets }}"
            class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-10 text-center">
            <x-heroicon-o-chart-bar class="w-10 h-10 mx-auto text-gray-200 dark:text-gray-700 mb-3" />
            <p class="text-sm text-gray-400 dark:text-gray-500">{{ __('expert-statistics::pbx.expert_statistics.my_users_no_data') }}</p>
        </div>

    @else
        <div wire:loading.remove wire:target="{{ $loadingTargets }}" class="space-y-6">

            {{-- An agent can be connected to several queues at the same time, so per-queue
                 durations below are independent values, never summed into a single total. --}}
            <p class="text-xs text-gray-400 dark:text-gray-500">
                {{ __('expert-statistics::pbx.expert_statistics.agent_monitoring_queue_connection_overlap_note') }}
            </p>

            @if ($showConsolidated)
                {{-- Consolidated view: one independent bar per queue, across all selected agents --}}
                @if (! empty($aggregateChart['categories']))
                    <x-expert-statistics::charts.stacked-bar-chart
                        :title="__('expert-statistics::pbx.expert_statistics.agent_monitoring_queue_connection_aggregate_title')"
                        :categories="$aggregateChart['categories']"
                        :series="$aggregateChart['series']"
                        :chartKey="'agg-' . crc32(json_encode($aggregateChart))"
                        :stacked="false"
                        :distributed="true"
                        :colors="$aggregateChart['colors']"
                    />
                @endif

                {{-- Daily grouped bar: time connected to each queue, per day, across all selected agents --}}
                @if (! empty($dailySeries['categories']))
                    <x-expert-statistics::charts.stacked-bar-chart
                        :title="__('expert-statistics::pbx.expert_statistics.agent_monitoring_queue_connection_daily_title')"
                        :categories="$dailySeries['categories']"
                        :series="$dailySeries['series']"
                        :chartKey="'daily-' . crc32(json_encode($dailySeries))"
                        :horizontal="false"
                        :stacked="false"
                    />
                @endif
            @else
                {{-- Detailed view: time connected to each queue, per agent --}}
                <x-expert-statistics::charts.stacked-bar-chart
                    :title="__('expert-statistics::pbx.expert_statistics.agent_monitoring_queue_connection_per_agent_title')"
                    :categories="$perAgentSeries['categories']"
                    :series="$perAgentSeries['series']"
                    :chartKey="'per-agent-' . crc32(json_encode($perAgentSeries))"
                    :stacked="false"
                />

                {{-- Heatmap: agent × queue connection time, darker = more time connected --}}
                <x-expert-statistics::charts.heatmap-chart
                    :title="__('expert-statistics::pbx.expert_statistics.agent_monitoring_queue_connection_heatmap_title')"
                    :series="$heatmapSeries"
                    :chartKey="'heatmap-' . crc32(json_encode($heatmapSeries))"
                />

                {{-- Daily grouped bar per agent: small multiples, one chart per selected agent --}}
                @if (count($dailySeriesPerAgent) === 1)
                    @php $agentDaily = $dailySeriesPerAgent[0]; @endphp
                    @if (! empty($agentDaily['categories']))
                        <x-expert-statistics::charts.stacked-bar-chart
                            :title="__('expert-statistics::pbx.expert_statistics.agent_monitoring_queue_connection_daily_title')"
                            :categories="$agentDaily['categories']"
                            :series="$agentDaily['series']"
                            :chartKey="'daily-' . crc32(json_encode($agentDaily))"
                            :horizontal="false"
                            :stacked="false"
                        />
                    @endif
                @elseif (count($dailySeriesPerAgent) > 1)
                    <div>
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-3">
                            {{ __('expert-statistics::pbx.expert_statistics.agent_monitoring_queue_connection_daily_title') }}
                        </h3>
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                            @foreach ($dailySeriesPerAgent as $agentDaily)
                                @if (! empty($agentDaily['categories']))
                                    <x-expert-statistics::charts.stacked-bar-chart
                                        :title="$this->getAgentLabel($agentDaily['user_dn'])"
                                        :categories="$agentDaily['categories']"
                                        :series="$agentDaily['series']"
                                        :chartKey="'daily-' . crc32(json_encode($agentDaily))"
                                        :horizontal="false"
                                        :stacked="false"
                                    />
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif
            @endif

            {{-- Detail table --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700/50">
                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                        {{ __('expert-statistics::pbx.expert_statistics.agent_monitoring_queue_connection_table_title') }}
                    </span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                @unless ($showConsolidated)
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">
                                        {{ __('expert-statistics::pbx.expert_statistics.agent_monitoring_col_agent') }}
                                    </th>
                                @endunless
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('expert-statistics::pbx.expert_statistics.agent_monitoring_queue_connection_col_queue') }}
                                </th>
                                <th class="px-4 py-2 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">
                                    {{ __('expert-statistics::pbx.expert_statistics.agent_monitoring_col_duration') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                            @if ($showConsolidated)
                                @forelse ($aggregateItems as $item)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center gap-2">
                                                <span class="w-2 h-2 rounded-full shrink-0" style="background-color: {{ $item['color'] }}"></span>
                                                <span class="text-gray-700 dark:text-gray-300">{{ $item['label'] }}</span>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                            {{ $this->formatMinutes($item['minutes']) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">
                                            {{ __('expert-statistics::pbx.expert_statistics.no_data') }}
                                        </td>
                                    </tr>
                                @endforelse
                            @else
                                @forelse ($perAgent as $agent)
                                    @foreach ($agent['queues'] as $index => $queue)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                            @if ($index === 0)
                                                <td rowspan="{{ count($agent['queues']) }}" class="px-4 py-3 text-gray-800 dark:text-gray-200 whitespace-nowrap font-medium align-top border-r border-gray-100 dark:border-gray-700/50">
                                                    {{ $this->getAgentLabel($agent['user_dn']) }}
                                                </td>
                                            @endif
                                            <td class="px-4 py-3">
                                                <span class="inline-flex items-center gap-2">
                                                    <span class="w-2 h-2 rounded-full shrink-0" style="background-color: {{ $this->getQueueColor($queue['queue_dn']) }}"></span>
                                                    <span class="text-gray-700 dark:text-gray-300">{{ $this->getQueueLabel($queue['queue_dn']) }}</span>
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-center text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                                {{ $this->formatMinutes($queue['total_minutes']) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">
                                            {{ __('expert-statistics::pbx.expert_statistics.no_data') }}
                                        </td>
                                    </tr>
                                @endforelse
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    @endif

</div>
