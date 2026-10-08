@use('CXEngine\ExpertStatistics\Support\CallAnalysisLink')
<x-expert-statistics::cluster-layout :title="__('expert-statistics::pbx.expert_statistics.my_users_outbound_title')">

<div class="space-y-5">
    <div class="flex justify-between items-center">
        {{-- Element selector --}}
        <x-expert-statistics::element-selector
            :urlType="$urlType"
            :pbxElements="$pbxElements"
            :selectedElements="$selectedElements"
            :groupSelectedName="$groupSelectedName"
        />

        <div class="flex items-center gap-2">
            <x-expert-statistics::report-action-buttons />
            <x-expert-statistics::export-button :url="$this->getExportUrl()" />
        </div>
    </div>

    {{-- Filter bar --}}
    <x-expert-statistics::filter-bar
        :selectedPeriod="$selectedPeriod"
        :startDate="$startDate"
        :endDate="$endDate"
        :startTime="$startTime"
        :endTime="$endTime"
        :showTimeRange="true"
    />

    {{-- What is counted --}}
    <div class="flex items-start gap-2 rounded-xl bg-sky-50 dark:bg-sky-950/30 ring-1 ring-sky-200 dark:ring-sky-900 px-4 py-3 text-sm text-sky-700 dark:text-sky-300">
        <x-heroicon-o-information-circle class="w-5 h-5 shrink-0" />
        <div>
            <p>{{ __('expert-statistics::pbx.expert_statistics.my_users_outbound_external_only') }}</p>
            <p class="text-xs text-sky-600/80 dark:text-sky-400/80 mt-0.5">{{ __('expert-statistics::pbx.expert_statistics.my_users_outbound_answered_note') }}</p>
        </div>
    </div>

    @if ($errorMessage)
        <div class="rounded-xl bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-900 px-4 py-3 text-sm text-red-700 dark:text-red-400">
            {{ $errorMessage }}
        </div>
    @endif

    {{-- Exclude closed hours toggle --}}
    <label class="inline-flex items-center gap-2 cursor-pointer">
        <input
            type="checkbox"
            wire:model.live="excludeClosedHours"
            class="rounded border-gray-300 dark:border-gray-600 text-primary-600"
        />
        <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('expert-statistics::pbx.expert_statistics.exclude_closed_hours') }}</span>
    </label>

    @php
        $loadingTargets = 'applyFilters,selectPeriod,setCustomRange,updateTimeRange,applyPbxElementSelection,selectAllPbxElements,clearPbxElements,removePbxElement,excludeClosedHours';
    @endphp

    {{-- Loading --}}
    <div wire:loading.flex wire:target="{{ $loadingTargets }}" class="items-center justify-center py-10">
        <div class="w-8 h-8 border-2 border-primary-500 border-t-transparent rounded-full animate-spin"></div>
    </div>

    {{-- Empty state --}}
    @if (! $dn)
    <div wire:loading.remove wire:target="{{ $loadingTargets }}" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-12 text-center">
        <x-heroicon-o-phone-arrow-up-right class="w-12 h-12 mx-auto text-gray-200 dark:text-gray-700 mb-4" />
        <h3 class="text-base font-semibold text-gray-600 dark:text-gray-400 mb-2">{{ __('expert-statistics::pbx.expert_statistics.my_users_outbound_title') }}</h3>
        <p class="text-sm text-gray-400 dark:text-gray-500">{{ __('expert-statistics::pbx.expert_statistics.my_users_empty') }}</p>
    </div>

    @elseif (empty($tableData))
    <div wire:loading.remove wire:target="{{ $loadingTargets }}" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-10 text-center">
        <x-heroicon-o-chart-bar class="w-10 h-10 mx-auto text-gray-200 dark:text-gray-700 mb-3" />
        <p class="text-sm text-gray-400 dark:text-gray-500">{{ __('expert-statistics::pbx.expert_statistics.my_users_no_data') }}</p>
    </div>

    @else
    @php
        $sortIcon = function (string $field) use ($sortField, $sortDirection): string {
            $active = $field === $sortField;
            $color = $active ? 'text-primary-600 dark:text-primary-400' : 'text-gray-300 dark:text-gray-600';
            $path = 'M8 4l4 4H4l4-4zm0 8l-4-4h8l-4 4z';
            if ($active) {
                $path = $sortDirection === 'asc' ? 'M8 4l4 5H4l4-5z' : 'M8 12l-4-5h8l-4 5z';
            }
            return '<svg xmlns="http://www.w3.org/2000/svg" class="inline-block ml-1 h-3 w-3 '.$color.'" viewBox="0 0 16 16" fill="currentColor"><path d="'.$path.'"/></svg>';
        };

        $columns = [
            'outbound_calls' => [__('expert-statistics::pbx.expert_statistics.my_users_outbound_col_calls'), __('expert-statistics::pbx.expert_statistics.my_users_outbound_tooltip_calls')],
            'outbound_answered' => [__('expert-statistics::pbx.expert_statistics.col_answered'), null],
            'outbound_unanswered' => [__('expert-statistics::pbx.expert_statistics.my_users_subh_unanswered'), null],
            'outbound_answered_percentage' => [__('expert-statistics::pbx.expert_statistics.col_answer_rate'), null],
            'outbound_talking_duration_total' => [__('expert-statistics::pbx.expert_statistics.my_users_subh_duration_total'), null],
            'outbound_talking_duration_avg' => [__('expert-statistics::pbx.expert_statistics.my_users_subh_duration_avg'), null],
            'outbound_ringing_duration_avg' => [__('expert-statistics::pbx.expert_statistics.my_users_outbound_col_ringing_avg'), __('expert-statistics::pbx.expert_statistics.my_users_outbound_tooltip_ringing_avg')],
            'outbound_unique_numbers' => [__('expert-statistics::pbx.expert_statistics.my_users_outbound_col_unique_numbers'), __('expert-statistics::pbx.expert_statistics.my_users_outbound_tooltip_unique_numbers')],
        ];

        // Drill-down: the external legs placed by the given extension(s).
        $cfaParams = [...CallAnalysisLink::period($startDate, $endDate, $startTime, $endTime), 'callWay' => 'outbound', 'originDnType' => '0', 'destinationDnType' => '1'];
        $cfaUrls = fn (string $dns): array => [
            'all' => CallAnalysisLink::url([...$cfaParams, 'originDn' => $dns, 'callStatus' => 'all']),
            'answered' => CallAnalysisLink::url([...$cfaParams, 'originDn' => $dns, 'callStatus' => 'answered']),
            'unanswered' => CallAnalysisLink::url([...$cfaParams, 'originDn' => $dns, 'callStatus' => 'unanswered']),
        ];
        $rateBadge = fn (float $pct): string => $pct >= 80
            ? 'bg-green-100 text-green-700 dark:bg-green-950/40 dark:text-green-400'
            : ($pct >= 60 ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-950/40 dark:text-yellow-400' : 'bg-red-100 text-red-700 dark:bg-red-950/40 dark:text-red-400');
    @endphp

    <div wire:loading.remove wire:target="{{ $loadingTargets }}" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <th wire:click="sortBy('user')" class="px-4 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-200 select-none whitespace-nowrap">{{ __('expert-statistics::pbx.expert_statistics.my_users_col_user') }}{!! $sortIcon('user') !!}</th>
                        @foreach ($columns as $field => [$label, $tooltip])
                            <th wire:click="sortBy('{{ $field }}')" @if ($tooltip) title="{{ $tooltip }}" @endif class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 cursor-pointer hover:text-gray-700 dark:hover:text-gray-200 select-none whitespace-nowrap">{{ $label }}{!! $sortIcon($field) !!}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                    @foreach ($this->getDisplayData() as $row)
                    @php
                        $urls = $cfaUrls((string) ($row['user_dn'] ?? explode('-', $row['user'] ?? '')[0]));
                        $calls = (int) ($row['outbound_calls'] ?? 0);
                        $pct = (float) ($row['outbound_answered_percentage'] ?? 0);
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                        <td class="px-4 py-3 text-gray-800 dark:text-gray-200 whitespace-nowrap font-medium">{{ $row['user'] ?? '—' }}</td>
                        <td class="px-3 py-3 text-right text-gray-800 dark:text-gray-200 font-medium"><x-expert-statistics::call-analysis-link :href="$urls['all']">{{ $calls }}</x-expert-statistics::call-analysis-link></td>
                        <td class="px-3 py-3 text-right text-green-600 dark:text-green-400"><x-expert-statistics::call-analysis-link :href="$urls['answered']">{{ $row['outbound_answered'] ?? 0 }}</x-expert-statistics::call-analysis-link></td>
                        <td class="px-3 py-3 text-right text-red-600 dark:text-red-400"><x-expert-statistics::call-analysis-link :href="$urls['unanswered']">{{ $row['outbound_unanswered'] ?? 0 }}</x-expert-statistics::call-analysis-link></td>
                        <td class="px-3 py-3 text-right">
                            @if ($calls > 0)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $rateBadge($pct) }}">{{ $pct }}%</span>
                            @else
                                <span class="text-gray-400 dark:text-gray-600">—</span>
                            @endif
                        </td>
                        <td class="px-3 py-3 text-right text-gray-600 dark:text-gray-400">{{ $this->formatDuration($row['outbound_talking_duration_total'] ?? 0) }}</td>
                        <td class="px-3 py-3 text-right text-gray-600 dark:text-gray-400">{{ $this->formatDuration($row['outbound_talking_duration_avg'] ?? 0) }}</td>
                        <td class="px-3 py-3 text-right text-gray-600 dark:text-gray-400">{{ $this->formatDuration($row['outbound_ringing_duration_avg'] ?? 0) }}</td>
                        <td class="px-3 py-3 text-right text-gray-600 dark:text-gray-400">{{ $row['outbound_unique_numbers'] ?? 0 }}</td>
                    </tr>
                    @endforeach
                </tbody>
                @if (! empty($aggregated))
                @php
                    $urls = $cfaUrls($dn);
                    $totalCalls = (int) ($aggregated['outbound_calls'] ?? 0);
                    $totalPct = (float) ($aggregated['outbound_answered_percentage'] ?? 0);
                @endphp
                <tfoot class="bg-gray-50 dark:bg-gray-900/30 border-t-2 border-gray-200 dark:border-gray-700 font-semibold">
                    <tr title="{{ __('expert-statistics::pbx.expert_statistics.my_users_outbound_tooltip_total') }}">
                        <td class="px-4 py-3 text-gray-800 dark:text-gray-200 whitespace-nowrap">{{ __('expert-statistics::pbx.expert_statistics.my_users_outbound_total') }}</td>
                        <td class="px-3 py-3 text-right text-gray-800 dark:text-gray-200"><x-expert-statistics::call-analysis-link :href="$urls['all']">{{ $totalCalls }}</x-expert-statistics::call-analysis-link></td>
                        <td class="px-3 py-3 text-right text-green-600 dark:text-green-400"><x-expert-statistics::call-analysis-link :href="$urls['answered']">{{ $aggregated['outbound_answered'] ?? 0 }}</x-expert-statistics::call-analysis-link></td>
                        <td class="px-3 py-3 text-right text-red-600 dark:text-red-400"><x-expert-statistics::call-analysis-link :href="$urls['unanswered']">{{ $aggregated['outbound_unanswered'] ?? 0 }}</x-expert-statistics::call-analysis-link></td>
                        <td class="px-3 py-3 text-right">
                            @if ($totalCalls > 0)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $rateBadge($totalPct) }}">{{ $totalPct }}%</span>
                            @else
                                <span class="text-gray-400 dark:text-gray-600">—</span>
                            @endif
                        </td>
                        <td class="px-3 py-3 text-right text-gray-600 dark:text-gray-400">{{ $this->formatDuration($aggregated['outbound_talking_duration_total'] ?? 0) }}</td>
                        <td class="px-3 py-3 text-right text-gray-600 dark:text-gray-400">{{ $this->formatDuration($aggregated['outbound_talking_duration_avg'] ?? 0) }}</td>
                        <td class="px-3 py-3 text-right text-gray-600 dark:text-gray-400">{{ $this->formatDuration($aggregated['outbound_ringing_duration_avg'] ?? 0) }}</td>
                        <td class="px-3 py-3 text-right text-gray-600 dark:text-gray-400">{{ $aggregated['outbound_unique_numbers'] ?? 0 }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
    @endif

    <livewire:expert-statistics.reports.share-report-modal :key="'expert-statistics.reports.share-report-modal'" />

</div>

</x-expert-statistics::cluster-layout>
