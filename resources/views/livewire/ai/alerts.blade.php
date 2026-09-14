@php
    // Category counts, purely for the Alpine-driven filter tabs below.
    $categoryGroups = [];
    foreach ($alerts as $alert) {
        $cat = $alert['category'] ?? 'other';
        $categoryGroups[$cat] = ($categoryGroups[$cat] ?? 0) + 1;
    }
    $filterTabs = array_filter([
        'all' => count($alerts),
        'queue_performance' => $categoryGroups['queue_performance'] ?? 0,
        'peak_hours' => $categoryGroups['peak_hours'] ?? 0,
        'volume' => $categoryGroups['volume'] ?? 0,
        'agent_performance' => $categoryGroups['agent_performance'] ?? 0,
    ], fn (int $count, string $key) => $key === 'all' || $count > 0, ARRAY_FILTER_USE_BOTH);

    $categoryLabels = [
        'all' => __('expert-statistics::pbx.expert_statistics.ai_alerts_filter_all'),
        'queue_performance' => __('expert-statistics::pbx.expert_statistics.ai_alerts_filter_queues'),
        'peak_hours' => __('expert-statistics::pbx.expert_statistics.ai_alerts_filter_peak_hours'),
        'volume' => __('expert-statistics::pbx.expert_statistics.ai_alerts_filter_volume'),
        'agent_performance' => __('expert-statistics::pbx.expert_statistics.ai_alerts_filter_agents'),
    ];
@endphp

<x-expert-statistics::cluster-layout :title="__('expert-statistics::pbx.expert_statistics.nav_ai_alerts')">

<div class="space-y-5">

    {{-- ── Header ─────────────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-center justify-end gap-3">
        <button
            wire:click="checkNow"
            wire:loading.attr="disabled"
            wire:target="checkNow"
            class="inline-flex items-center gap-1.5 px-4 py-2 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
        >
            <x-heroicon-o-bolt class="w-4 h-4" wire:loading.class="animate-pulse" wire:target="checkNow" />
            {{ __('expert-statistics::pbx.expert_statistics.ai_alerts_check_now') }}
        </button>
    </div>

    {{-- ── Summary KPI cards ───────────────────────────────────────────────── --}}
    @if (! empty($summary))
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach ([
                ['key' => 'total', 'label' => __('expert-statistics::pbx.expert_statistics.ai_alerts_summary_total'), 'color' => 'primary'],
                ['key' => 'critical', 'label' => __('expert-statistics::pbx.expert_statistics.ai_alerts_summary_critical'), 'color' => 'red'],
                ['key' => 'warning', 'label' => __('expert-statistics::pbx.expert_statistics.ai_alerts_summary_warning'), 'color' => 'yellow'],
                ['key' => 'info', 'label' => __('expert-statistics::pbx.expert_statistics.ai_alerts_summary_info'), 'color' => 'blue'],
            ] as $kpi)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ $kpi['label'] }}</p>
                            <p @class([
                                'text-3xl font-bold mt-1',
                                'text-primary-600 dark:text-primary-400' => $kpi['color'] === 'primary',
                                'text-red-500 dark:text-red-400' => $kpi['color'] === 'red',
                                'text-yellow-500 dark:text-yellow-400' => $kpi['color'] === 'yellow',
                                'text-blue-500 dark:text-blue-400' => $kpi['color'] === 'blue',
                            ])>
                                {{ $summary[$kpi['key']] ?? 0 }}
                            </p>
                        </div>
                        <div @class([
                            'p-2.5 rounded-full',
                            'bg-primary-100 dark:bg-primary-950/40' => $kpi['color'] === 'primary',
                            'bg-red-100 dark:bg-red-950/40' => $kpi['color'] === 'red',
                            'bg-yellow-100 dark:bg-yellow-950/40' => $kpi['color'] === 'yellow',
                            'bg-blue-100 dark:bg-blue-950/40' => $kpi['color'] === 'blue',
                        ])>
                            @if ($kpi['color'] === 'red')
                                <x-heroicon-o-exclamation-circle class="w-6 h-6 text-red-500 dark:text-red-400" />
                            @elseif ($kpi['color'] === 'yellow')
                                <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-yellow-500 dark:text-yellow-400" />
                            @elseif ($kpi['color'] === 'blue')
                                <x-heroicon-o-information-circle class="w-6 h-6 text-blue-500 dark:text-blue-400" />
                            @else
                                <x-heroicon-o-bell-alert class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- ── Error ───────────────────────────────────────────────────────────── --}}
    @if ($errorMessage)
        <div class="rounded-xl bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-900 px-4 py-3 text-sm text-red-700 dark:text-red-400">
            {{ $errorMessage }}
        </div>
    @endif

    {{-- ── Empty state ─────────────────────────────────────────────────────── --}}
    @if (! $errorMessage && empty($alerts))
        <div class="px-5 py-16 text-center bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
            <div class="w-12 h-12 bg-green-50 dark:bg-green-950/30 rounded-full flex items-center justify-center mx-auto mb-3">
                <x-heroicon-o-check-circle class="w-6 h-6 text-green-500 dark:text-green-400" />
            </div>
            <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-400 mb-1">
                {{ __('expert-statistics::pbx.expert_statistics.ai_alerts_empty_title') }}
            </h3>
            <p class="text-sm text-gray-400 dark:text-gray-500">
                {{ __('expert-statistics::pbx.expert_statistics.ai_alerts_empty_description') }}
            </p>
        </div>

    {{-- ── Alert list with Alpine category filter ──────────────────────────── --}}
    @else
        <div x-data="{ activeCategory: 'all' }" class="space-y-0">
            {{-- Category filter tabs --}}
            @if (count($filterTabs) > 1)
                <div class="flex gap-1 flex-wrap pb-3">
                    @foreach ($filterTabs as $catKey => $catCount)
                        <button
                            @click="activeCategory = '{{ $catKey }}'"
                            :class="activeCategory === '{{ $catKey }}'
                                ? 'bg-teal-50 text-teal-700 border-teal-200 dark:bg-teal-950/30 dark:text-teal-400 dark:border-teal-800'
                                : 'bg-gray-50 text-gray-500 border-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700'"
                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full border text-xs font-medium transition-colors whitespace-nowrap"
                        >
                            <span>{{ $categoryLabels[$catKey] ?? ucfirst(str_replace('_', ' ', $catKey)) }}</span>
                            @if ($catCount > 0 && $catKey !== 'all')
                                <span
                                    :class="activeCategory === '{{ $catKey }}' ? 'bg-teal-100 text-teal-700 dark:bg-teal-900 dark:text-teal-400' : 'bg-gray-200 text-gray-500 dark:bg-gray-700 dark:text-gray-400'"
                                    class="text-xs font-bold rounded-full px-1.5 min-w-4 text-center leading-4"
                                >{{ $catCount }}</span>
                            @endif
                        </button>
                    @endforeach
                </div>
            @endif

            {{-- Alert cards --}}
            <div class="space-y-3">
                @foreach ($alerts as $alert)
                    @php
                        $severity = $alert['severity'] ?? 'info';
                        $category = $alert['category'] ?? '';
                        $alertId = (int) ($alert['id'] ?? 0);
                        $isRate = str_contains((string) ($alert['metric'] ?? ''), 'rate');
                        $cardClass = match ($severity) {
                            'critical' => 'border-red-200 dark:border-red-800',
                            'warning' => 'border-yellow-200 dark:border-yellow-800',
                            default => 'border-blue-100 dark:border-blue-800',
                        };
                        $dotClass = match ($severity) {
                            'critical' => 'bg-red-500',
                            'warning' => 'bg-yellow-400',
                            default => 'bg-blue-400',
                        };
                        $valClass = match ($severity) {
                            'critical' => 'text-red-600 dark:text-red-400',
                            'warning' => 'text-yellow-500 dark:text-yellow-400',
                            default => 'text-blue-500 dark:text-blue-400',
                        };
                        $barClass = match ($severity) {
                            'critical' => 'bg-red-400',
                            'warning' => 'bg-yellow-400',
                            default => 'bg-blue-400',
                        };
                    @endphp

                    <div
                        wire:key="ai-alert-{{ $alertId }}"
                        x-show="activeCategory === 'all' || activeCategory === '{{ $category }}'"
                        x-data="{ open: false }"
                        x-transition
                        @class(['rounded-xl border overflow-hidden bg-white dark:bg-gray-800', $cardClass])
                    >
                        {{-- ── Card header (always visible, click to expand) ── --}}
                        <div class="flex items-start gap-2.5 px-4 py-3 cursor-pointer select-none" @click="open = !open">
                            <div class="shrink-0 mt-1">
                                <div class="w-2 h-2 rounded-full mt-1 {{ $dotClass }}"></div>
                            </div>

                            <div class="shrink-0 w-7 h-7 rounded-lg flex items-center justify-center {{ $this->getCategoryIconClass($category) }}">
                                @if ($category === 'queue_performance')
                                    <x-heroicon-o-phone class="w-3.5 h-3.5" />
                                @elseif ($category === 'peak_hours')
                                    <x-heroicon-o-clock class="w-3.5 h-3.5" />
                                @elseif ($category === 'volume')
                                    <x-heroicon-o-chart-bar class="w-3.5 h-3.5" />
                                @elseif ($category === 'agent_performance')
                                    <x-heroicon-o-user-group class="w-3.5 h-3.5" />
                                @else
                                    <x-heroicon-o-bell-alert class="w-3.5 h-3.5" />
                                @endif
                            </div>

                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-semibold text-gray-800 dark:text-gray-200 leading-tight">
                                    {{ $alert['title'] ?? '' }}
                                </p>
                                @if (! empty($alert['affected_name']) || ! empty($alert['affected_dn']))
                                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                                        {{ $alert['affected_name'] ?? $alert['affected_dn'] ?? '' }}
                                    </p>
                                @endif
                            </div>

                            <div class="shrink-0 flex flex-col items-end gap-0.5">
                                @if (isset($alert['current_value']) && $alert['current_value'] !== null)
                                    <span class="text-sm font-bold leading-none {{ $valClass }}">
                                        {{ $alert['current_value'] }}{{ $isRate ? '%' : '' }}
                                    </span>
                                @endif
                                @if (isset($alert['change_percent']) && $alert['change_percent'] !== null)
                                    @php $chgPct = (float) $alert['change_percent']; @endphp
                                    <span @class([
                                        'text-xs font-semibold px-1.5 py-0.5 rounded-full leading-none',
                                        'text-red-500 bg-red-50 dark:text-red-400 dark:bg-red-950/30' => $chgPct > 0,
                                        'text-green-600 bg-green-50 dark:text-green-400 dark:bg-green-950/30' => $chgPct <= 0,
                                    ])>
                                        {{ $chgPct > 0 ? '▲' : '▼' }}{{ abs($chgPct) }}%
                                    </span>
                                @endif
                            </div>

                            <div class="shrink-0 ml-1">
                                <svg :class="open ? 'rotate-180' : ''" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2.5" class="transition-transform">
                                    <polyline points="6 9 12 15 18 9"/>
                                </svg>
                            </div>
                        </div>

                        {{-- ── Expanded body ──────────────────────────────── --}}
                        <div
                            x-show="open"
                            x-transition
                            class="px-4 pb-4 border-t {{ match ($severity) { 'critical' => 'border-red-100 dark:border-red-900', 'warning' => 'border-yellow-100 dark:border-yellow-900', default => 'border-blue-100 dark:border-blue-900' } }}"
                        >
                            @if (! empty($alert['description']))
                                <p class="text-xs text-gray-600 dark:text-gray-400 leading-relaxed pt-3">
                                    {{ $alert['description'] }}
                                </p>
                            @endif

                            @if ($isRate && isset($alert['current_value']))
                                <div class="mt-3 mb-1">
                                    <div class="flex justify-between text-xs text-gray-400 dark:text-gray-500 mb-1">
                                        <span>{{ ucfirst(str_replace('_', ' ', (string) ($alert['metric'] ?? ''))) }}</span>
                                        <span>{{ $alert['current_value'] }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-1.5 overflow-hidden">
                                        <div class="{{ $barClass }} h-full rounded-full" style="width: {{ min((float) ($alert['current_value'] ?? 0), 100) }}%"></div>
                                    </div>
                                </div>
                            @endif

                            @if (! empty($alert['recommendation']))
                                <div class="flex gap-2 mt-3 bg-yellow-50 border border-yellow-100 dark:bg-yellow-950/20 dark:border-yellow-800 rounded-lg px-3 py-2">
                                    <x-heroicon-o-information-circle class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" />
                                    <p class="text-xs text-yellow-800 dark:text-yellow-300 leading-relaxed">
                                        {{ $alert['recommendation'] }}
                                    </p>
                                </div>
                            @endif

                            <div class="flex items-center justify-between mt-3">
                                <span class="text-xs text-gray-400 dark:text-gray-500">
                                    @if (! empty($alert['created_at']))
                                        {{ \Carbon\Carbon::parse($alert['created_at'])->diffForHumans() }}
                                    @endif
                                </span>
                                @if ($alertId)
                                    <button
                                        @click.stop
                                        wire:click="dismissAlert({{ $alertId }})"
                                        wire:confirm="{{ __('expert-statistics::pbx.expert_statistics.ai_alerts_dismiss_confirm') }}"
                                        class="flex items-center gap-1 text-xs text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-md px-2 py-1 transition-colors"
                                    >
                                        <x-heroicon-m-x-mark class="w-3 h-3" />
                                        {{ __('expert-statistics::pbx.expert_statistics.ai_alerts_dismiss') }}
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

</div>

</x-expert-statistics::cluster-layout>
