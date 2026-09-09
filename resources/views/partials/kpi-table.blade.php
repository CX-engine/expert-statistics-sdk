{{--
    Shared KPI/Dashboard per-DN table partial.
    Variables expected:
      $title          — string, card header (DN number or "Consolidé")
      $granularity    — string, current granularity key (hour|day|weekday|week|month)
      $rows           — array of [{period, answered, unanswered, total, rate, avg_wait}]
      $formatDuration — optional callable; falls back to inline formatter
--}}
@php
    $formatDuration ??= function (mixed $seconds): string {
        $secs = (int) $seconds;
        if ($secs <= 0) {
            return '0s';
        }
        $hours = intdiv($secs, 3600);
        $mins = intdiv($secs % 3600, 60);
        $remaining = $secs % 60;
        if ($hours > 0) {
            return "{$hours}h {$mins}m";
        }

        return $mins > 0 ? "{$mins}m {$remaining}s" : "{$remaining}s";
    };
@endphp
<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden">

    {{-- Card header --}}
    <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-700/50 flex items-center gap-2">
        <x-heroicon-o-chart-bar-square class="w-4 h-4 text-primary-500 shrink-0" />
        <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $title }}</span>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            <thead class="bg-gray-50 dark:bg-gray-900/50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        @switch($granularity)
                            @case('hour')
                            @case('weekday')
                                {{ __('expert-statistics::pbx.expert_statistics.kpi_col_slot') }}
                                @break
                            @case('week')
                                {{ __('expert-statistics::pbx.expert_statistics.kpi_col_week') }}
                                @break
                            @case('month')
                                {{ __('expert-statistics::pbx.expert_statistics.kpi_col_month') }}
                                @break
                            @default
                                {{ __('expert-statistics::pbx.expert_statistics.col_date') }}
                        @endswitch
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        {{ __('expert-statistics::pbx.expert_statistics.col_calls') }}
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-green-600 dark:text-green-400 uppercase tracking-wider">
                        {{ __('expert-statistics::pbx.expert_statistics.kpi_answered') }}
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-red-500 dark:text-red-400 uppercase tracking-wider">
                        {{ __('expert-statistics::pbx.expert_statistics.kpi_unanswered') }}
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        {{ __('expert-statistics::pbx.expert_statistics.col_answer_rate') }}
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        {{ __('expert-statistics::pbx.expert_statistics.kpi_avg_wait') }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                @forelse ($rows as $row)
                    @php
                        $pct = (float) ($row['rate'] ?? 0);
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                        <td class="px-4 py-2.5 font-mono text-xs text-gray-700 dark:text-gray-300 whitespace-nowrap">
                            {{ $row['period'] }}
                        </td>
                        <td class="px-4 py-2.5 text-right text-gray-700 dark:text-gray-300">
                            {{ number_format($row['total']) }}
                        </td>
                        <td class="px-4 py-2.5 text-right text-green-600 dark:text-green-400 font-medium">
                            {{ number_format($row['answered']) }}
                        </td>
                        <td class="px-4 py-2.5 text-right text-red-500 dark:text-red-400">
                            {{ number_format($row['unanswered']) }}
                        </td>
                        <td class="px-4 py-2.5 text-right">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                {{ $pct >= 80 ? 'bg-green-100 text-green-700 dark:bg-green-950/40 dark:text-green-400'
                                    : ($pct >= 60 ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-950/40 dark:text-yellow-400'
                                    : 'bg-red-100 text-red-700 dark:bg-red-950/40 dark:text-red-400') }}">
                                {{ $pct }}%
                            </span>
                        </td>
                        <td class="px-4 py-2.5 text-right text-gray-500 dark:text-gray-400">
                            {{ $formatDuration($row['avg_wait'] ?? 0) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6"
                            class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">
                            {{ __('expert-statistics::pbx.expert_statistics.no_data') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
