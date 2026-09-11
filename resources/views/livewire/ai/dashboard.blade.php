<x-pages.index
    :title="__('expert-statistics::pbx.expert_statistics.nav_ai_insights')"
    :subtitle="__('expert-statistics::pbx.expert_statistics.ai_dashboard_subtitle')"
>

<div class="space-y-5">

    {{-- ── Header ─────────────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-center justify-between gap-3">

        {{-- Left: AI icon + subtitle + unread badge --}}
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-linear-to-br from-teal-600 to-purple-500 flex items-center justify-center shrink-0 shadow-sm">
                <x-heroicon-o-sparkles class="w-5 h-5 text-white" />
            </div>
            <div>
                @if (! empty($meta['last_generated_at']))
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                        {{ __('expert-statistics::pbx.expert_statistics.ai_dashboard_last_generated') }}: {{ \Carbon\Carbon::parse($meta['last_generated_at'])->diffForHumans() }}
                    </p>
                @endif
            </div>
            @if (($meta['unread_count'] ?? 0) > 0)
                <span class="inline-flex items-center rounded-full bg-teal-50 border border-teal-200 dark:bg-teal-950/30 dark:border-teal-800 px-2.5 py-0.5 text-xs font-semibold text-teal-700 dark:text-teal-400">
                    {{ $meta['unread_count'] }} {{ __('expert-statistics::pbx.expert_statistics.ai_dashboard_unread') }}
                </span>
            @endif
        </div>

        {{-- Right: Mark all read + Refresh --}}
        <div class="flex items-center gap-2 shrink-0">
            @if (($meta['unread_count'] ?? 0) > 0)
                <button
                    wire:click="markAllRead"
                    class="inline-flex items-center gap-1.5 px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm text-xs font-medium text-gray-600 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                >
                    <x-heroicon-o-check class="w-3.5 h-3.5" />
                    {{ __('expert-statistics::pbx.expert_statistics.ai_dashboard_mark_all_read') }}
                </button>
            @endif
            <button
                wire:click="refresh"
                wire:loading.attr="disabled"
                wire:target="refresh"
                class="inline-flex items-center gap-1.5 px-4 py-2 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <x-heroicon-o-arrow-path class="w-4 h-4" wire:loading.class="animate-spin" wire:target="refresh" />
                {{ __('expert-statistics::pbx.expert_statistics.ai_dashboard_refresh') }}
            </button>
        </div>
    </div>

    {{-- ── Meta stats row ──────────────────────────────────────────────────── --}}
    @if (! empty($meta))
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            {{-- Total panels --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 border-l-4 border-l-teal-400 px-4 py-3 flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-teal-50 dark:bg-teal-950/30 flex items-center justify-center shrink-0">
                    <x-heroicon-o-squares-2x2 class="w-4 h-4 text-teal-600 dark:text-teal-400" />
                </div>
                <div>
                    <p class="text-lg font-bold text-gray-800 dark:text-gray-200 leading-none">{{ $meta['total_panels'] ?? 0 }}</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ __('expert-statistics::pbx.expert_statistics.ai_dashboard_total_panels') }}</p>
                </div>
            </div>

            {{-- Active alerts --}}
            @php $alertsCount = (int) ($meta['active_alerts_count'] ?? 0); @endphp
            <div @class([
                'bg-white dark:bg-gray-800 rounded-lg shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 px-4 py-3 flex items-center gap-3',
                'border-l-4 border-l-red-400' => $alertsCount > 0,
            ])>
                <div @class([
                    'w-8 h-8 rounded-lg flex items-center justify-center shrink-0',
                    'bg-red-50 dark:bg-red-950/30' => $alertsCount > 0,
                    'bg-gray-50 dark:bg-gray-700' => $alertsCount === 0,
                ])>
                    <x-heroicon-o-bell @class([
                        'w-4 h-4',
                        'text-red-500 dark:text-red-400' => $alertsCount > 0,
                        'text-gray-400 dark:text-gray-500' => $alertsCount === 0,
                    ]) />
                </div>
                <div>
                    <p @class([
                        'text-lg font-bold leading-none',
                        'text-red-600 dark:text-red-400' => $alertsCount > 0,
                        'text-gray-800 dark:text-gray-200' => $alertsCount === 0,
                    ])>{{ $alertsCount }}</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ __('expert-statistics::pbx.expert_statistics.ai_dashboard_active_alerts') }}</p>
                </div>
            </div>

            {{-- Conversations --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 border-l-4 border-l-purple-400 px-4 py-3 flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-purple-50 dark:bg-purple-950/30 flex items-center justify-center shrink-0">
                    <x-heroicon-o-chat-bubble-left-right class="w-4 h-4 text-purple-600 dark:text-purple-400" />
                </div>
                <div>
                    <p class="text-lg font-bold text-gray-800 dark:text-gray-200 leading-none">{{ $meta['total_conversations'] ?? 0 }}</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ __('expert-statistics::pbx.expert_statistics.ai_dashboard_conversations') }}</p>
                </div>
            </div>
        </div>
    @endif

    {{-- ── Error ───────────────────────────────────────────────────────────── --}}
    @if ($errorMessage)
        <div class="rounded-xl bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-900 px-4 py-3 text-sm text-red-700 dark:text-red-400">
            {{ $errorMessage }}
        </div>
    @endif

    {{-- ── Loading ─────────────────────────────────────────────────────────── --}}
    <div wire:loading.flex wire:target="refresh" class="items-center justify-center gap-1.5 py-16">
        <span class="w-2.5 h-2.5 bg-teal-400 rounded-full animate-bounce [animation-delay:0ms]"></span>
        <span class="w-2.5 h-2.5 bg-teal-400 rounded-full animate-bounce [animation-delay:150ms]"></span>
        <span class="w-2.5 h-2.5 bg-teal-400 rounded-full animate-bounce [animation-delay:300ms]"></span>
    </div>

    {{-- ── Main content ─────────────────────────────────────────────────────── --}}
    <div wire:loading.remove wire:target="refresh">

        @if (! $errorMessage && empty($panels))
            {{-- Empty state --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 py-16 text-center">
                <div class="w-14 h-14 rounded-full bg-teal-50 dark:bg-teal-950/30 flex items-center justify-center mx-auto mb-4">
                    <x-heroicon-o-sparkles class="w-6 h-6 text-teal-500 dark:text-teal-400" />
                </div>
                <h3 class="text-base font-semibold text-gray-700 dark:text-gray-300 mb-1">
                    {{ __('expert-statistics::pbx.expert_statistics.ai_dashboard_empty_title') }}
                </h3>
                <p class="text-sm text-gray-400 dark:text-gray-500 max-w-sm mx-auto mb-6">
                    {{ __('expert-statistics::pbx.expert_statistics.ai_dashboard_empty_description') }}
                </p>
                <button
                    wire:click="refresh"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-medium text-white bg-linear-to-r from-teal-600 to-purple-500 shadow-sm hover:shadow-md hover:opacity-90 transition-all"
                >
                    <x-heroicon-o-bolt class="w-4 h-4" />
                    {{ __('expert-statistics::pbx.expert_statistics.ai_dashboard_generate_insights') }}
                </button>
            </div>
        @else
            {{-- ── Panels grid ─────────────────────────────────────────────── --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                @foreach ($panels as $panel)
                    @php
                        $severity = $panel['severity'] ?? 'info';
                        $type = $panel['type'] ?? 'suggestion';
                        $isRead = (bool) ($panel['is_read'] ?? true);
                        $panelId = (string) ($panel['id'] ?? '');
                        $colSpan = $this->getPanelColSpan($type);
                        $data = $panel['data'] ?? [];
                    @endphp

                    <div
                        wire:key="ai-panel-{{ $panelId }}"
                        @class([
                            'flex flex-col rounded-xl shadow-sm ring-1 overflow-hidden transition-all duration-200 hover:shadow-lg',
                            $colSpan,
                            $this->getPanelBorderClass($severity),
                            $isRead
                                ? 'bg-white dark:bg-gray-800 ring-gray-950/5 dark:ring-white/10'
                                : 'bg-linear-to-br from-white to-teal-50/30 dark:from-gray-800 dark:to-teal-950/10 ring-teal-200/50 dark:ring-teal-800/30',
                        ])>

                        {{-- ── Panel header ────────────────────────────────── --}}
                        <div class="px-4 pt-4 pb-3 flex items-start gap-3">

                            {{-- Type icon --}}
                            <div @class(['w-8 h-8 rounded-lg flex items-center justify-center shrink-0 mt-0.5', $this->getTypeIconBgClass($type)])>
                                @if ($type === 'alert_summary')
                                    <x-heroicon-o-bell-alert class="w-4 h-4" />
                                @elseif ($type === 'trend' || $type === 'kpi_snapshot')
                                    <x-heroicon-o-chart-bar class="w-4 h-4" />
                                @elseif ($type === 'pattern')
                                    <x-heroicon-o-clock class="w-4 h-4" />
                                @elseif ($type === 'recommendation')
                                    <x-heroicon-o-information-circle class="w-4 h-4" />
                                @elseif ($type === 'suggestion')
                                    <x-heroicon-o-user class="w-4 h-4" />
                                @else
                                    <x-heroicon-o-bolt class="w-4 h-4" />
                                @endif
                            </div>

                            <div
                                class="flex-1 min-w-0"
                                @if ($panelId && ! $isRead) wire:click="readPanel('{{ $panelId }}')" @endif
                            >
                                <div class="flex items-center gap-2 flex-wrap">
                                    {{-- Type badge --}}
                                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $this->getTypeBadgeClass($type) }}">
                                        {{ $this->getPanelTypeLabel($type) }}
                                    </span>
                                    {{-- Severity badge (skip 'info' as it's the default) --}}
                                    @if (in_array($severity, ['critical', 'warning']))
                                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $this->getPanelSeverityClass($severity) }}">
                                            {{ __('expert-statistics::pbx.expert_statistics.ai_dashboard_severity_'.$severity) }}
                                        </span>
                                    @endif
                                    {{-- Unread pulse dot --}}
                                    @if (! $isRead)
                                        <span class="w-2 h-2 rounded-full bg-teal-500 shrink-0 animate-pulse"></span>
                                    @endif
                                </div>
                                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 leading-tight mt-1.5">
                                    {{ $panel['title'] ?? '' }}
                                </h3>
                            </div>

                            {{-- Dismiss button --}}
                            @if ($panelId)
                                <button
                                    wire:click="dismissPanel('{{ $panelId }}')"
                                    wire:confirm="{{ __('expert-statistics::pbx.expert_statistics.ai_dashboard_dismiss_confirm') }}"
                                    class="shrink-0 w-6 h-6 rounded-md flex items-center justify-center text-gray-300 dark:text-gray-600 hover:text-gray-500 dark:hover:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                >
                                    <x-heroicon-m-x-mark class="w-3.5 h-3.5" />
                                </button>
                            @endif
                        </div>

                        {{-- ── Description ─────────────────────────────────── --}}
                        @if (! empty($panel['description']))
                            <div class="px-4 pb-3 text-sm text-gray-500 dark:text-gray-400 leading-relaxed">
                                {{ $panel['description'] }}
                            </div>
                        @endif

                        {{-- ── kpi_snapshot / trend: metrics array ─────────── --}}
                        @if (in_array($type, ['kpi_snapshot', 'trend']) && ! empty($data['metrics']))
                            <div class="px-4 pb-3 grid grid-cols-2 gap-3">
                                @foreach ($data['metrics'] as $metric)
                                    <div class="bg-gray-50 dark:bg-gray-900/50 border border-gray-100 dark:border-gray-700 rounded-lg px-3 py-2.5">
                                        <p class="text-xs text-gray-400 dark:text-gray-500 mb-0.5">{{ $metric['label'] ?? '' }}</p>
                                        <p class="text-xl font-bold text-gray-800 dark:text-gray-200 leading-none">
                                            {{ $metric['value'] ?? '' }}
                                            @if (! empty($metric['unit']))
                                                <span class="text-xs font-normal text-gray-400 ml-1">{{ $metric['unit'] }}</span>
                                            @endif
                                        </p>
                                        @if (isset($metric['change_percent']) && ! empty($metric['change_direction']))
                                            @php
                                                $dir = $metric['change_direction'];
                                                $pct = abs((float) ($metric['change_percent'] ?? 0));
                                                $chgClass = match ($dir) {
                                                    'up' => 'text-green-600 bg-green-50 dark:text-green-400 dark:bg-green-950/30',
                                                    'down' => 'text-red-500 bg-red-50 dark:text-red-400 dark:bg-red-950/30',
                                                    default => 'text-gray-500 bg-gray-100 dark:bg-gray-700',
                                                };
                                            @endphp
                                            <span class="inline-flex items-center gap-0.5 text-xs font-semibold px-1.5 py-0.5 rounded-full mt-1 {{ $chgClass }}">
                                                {{ $dir === 'up' ? '▲' : ($dir === 'down' ? '▼' : '') }}
                                                {{ $pct }}%
                                            </span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        {{-- ── alert_summary ────────────────────────────────── --}}
                        @if ($type === 'alert_summary' && ! empty($data))
                            <div class="px-4 pb-3">
                                <div class="flex flex-wrap gap-2 mb-3">
                                    @if (! empty($data['critical']))
                                        <span class="inline-flex items-center gap-1 bg-red-50 border border-red-200 text-red-700 dark:bg-red-950/30 dark:border-red-800 dark:text-red-400 rounded-full px-2.5 py-0.5 text-xs font-semibold">
                                            <span class="w-1.5 h-1.5 rounded-full bg-red-500 shrink-0"></span>
                                            {{ $data['critical'] }} {{ __('expert-statistics::pbx.expert_statistics.ai_dashboard_critical') }}
                                        </span>
                                    @endif
                                    @if (! empty($data['warning']))
                                        <span class="inline-flex items-center gap-1 bg-yellow-50 border border-yellow-200 text-yellow-700 dark:bg-yellow-950/30 dark:border-yellow-800 dark:text-yellow-400 rounded-full px-2.5 py-0.5 text-xs font-semibold">
                                            <span class="w-1.5 h-1.5 rounded-full bg-yellow-400 shrink-0"></span>
                                            {{ $data['warning'] }} {{ __('expert-statistics::pbx.expert_statistics.ai_dashboard_warning') }}
                                        </span>
                                    @endif
                                    @if (! empty($data['info']))
                                        <span class="inline-flex items-center gap-1 bg-blue-50 border border-blue-200 text-blue-700 dark:bg-blue-950/30 dark:border-blue-800 dark:text-blue-400 rounded-full px-2.5 py-0.5 text-xs font-semibold">
                                            <span class="w-1.5 h-1.5 rounded-full bg-blue-400 shrink-0"></span>
                                            {{ $data['info'] }} {{ __('expert-statistics::pbx.expert_statistics.ai_dashboard_info') }}
                                        </span>
                                    @endif
                                </div>
                                @if (! empty($data['alerts']))
                                    <div class="space-y-2">
                                        @foreach (array_slice($data['alerts'], 0, 3) as $alertItem)
                                            @php
                                                $aSev = $alertItem['severity'] ?? 'info';
                                                $aCard = match ($aSev) {
                                                    'critical' => 'border-red-200 bg-red-50/40 dark:border-red-800 dark:bg-red-950/20',
                                                    'warning' => 'border-yellow-200 bg-yellow-50/30 dark:border-yellow-800 dark:bg-yellow-950/20',
                                                    default => 'border-blue-100 bg-blue-50/20 dark:border-blue-800 dark:bg-blue-950/10',
                                                };
                                                $aDot = match ($aSev) {
                                                    'critical' => 'bg-red-500',
                                                    'warning' => 'bg-yellow-400',
                                                    default => 'bg-blue-400',
                                                };
                                            @endphp
                                            <div class="flex items-start gap-2 rounded-lg border px-3 py-2 {{ $aCard }}">
                                                <div class="w-1.5 h-1.5 rounded-full mt-1.5 shrink-0 {{ $aDot }}"></div>
                                                <div class="min-w-0 flex-1">
                                                    <p class="text-xs font-semibold text-gray-800 dark:text-gray-200 truncate">{{ $alertItem['title'] ?? '' }}</p>
                                                    @if (! empty($alertItem['affected_name']) || ! empty($alertItem['affected_dn']))
                                                        <p class="text-xs text-gray-400 dark:text-gray-500">{{ $alertItem['affected_name'] ?? $alertItem['affected_dn'] ?? '' }}</p>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                        @if (count($data['alerts']) > 3)
                                            <p class="text-xs text-teal-600 dark:text-teal-400 font-medium text-center pt-2">
                                                +{{ count($data['alerts']) - 3 }} {{ __('expert-statistics::pbx.expert_statistics.ai_dashboard_more_alerts') }}
                                            </p>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endif

                        {{-- ── pattern: key_values ─────────────────────────── --}}
                        @if ($type === 'pattern' && ! empty($data['key_values']))
                            <div class="px-4 pb-3">
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($data['key_values'] as $key => $val)
                                        <span class="inline-flex items-center gap-1 bg-orange-50 border border-orange-100 text-orange-700 dark:bg-orange-950/30 dark:border-orange-800 dark:text-orange-400 rounded-lg px-2.5 py-1 text-xs font-medium">
                                            <span class="font-normal opacity-70">{{ str_replace('_', ' ', (string) $key) }}:</span>
                                            {{ is_array($val) ? implode(', ', \Illuminate\Support\Arr::flatten($val)) : $val }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- ── recommendation: action_items ───────────────────── --}}
                        @if ($type === 'recommendation' && ! empty($data['action_items']))
                            <div class="px-4 pb-3 space-y-2">
                                @foreach ($data['action_items'] as $idx => $item)
                                    <div class="flex gap-2.5 bg-blue-50 border border-blue-100 dark:bg-blue-950/20 dark:border-blue-800 rounded-lg px-3 py-2">
                                        <span class="w-5 h-5 rounded-full bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400 text-xs font-bold flex items-center justify-center shrink-0 mt-0.5">
                                            {{ $item['priority'] ?? ($idx + 1) }}
                                        </span>
                                        <div>
                                            <p class="text-xs font-semibold text-blue-800 dark:text-blue-300">{{ $item['action'] ?? '' }}</p>
                                            @if (! empty($item['rationale']))
                                                <p class="text-xs text-blue-600 dark:text-blue-400 mt-0.5">{{ $item['rationale'] }}</p>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        {{-- ── suggestion: entity + metrics ───────────────────── --}}
                        @if ($type === 'suggestion' && ! empty($data))
                            <div class="px-4 pb-3">
                                @if (! empty($data['entity_name']) || ! empty($data['entity_dn']))
                                    <p class="text-xs text-gray-400 dark:text-gray-500 mb-2">
                                        {{ ($data['entity_type'] ?? '') === 'queue' ? __('expert-statistics::pbx.expert_statistics.ai_dashboard_queue') : __('expert-statistics::pbx.expert_statistics.ai_dashboard_agent') }}:
                                        <span class="font-semibold text-gray-600 dark:text-gray-300">{{ $data['entity_name'] ?? $data['entity_dn'] ?? '' }}</span>
                                    </p>
                                @endif
                                @if (! empty($data['metrics']))
                                    <div class="grid grid-cols-3 gap-2">
                                        @foreach ($data['metrics'] as $metric)
                                            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg px-2.5 py-2 text-center">
                                                <p class="text-base font-bold text-gray-800 dark:text-gray-200 leading-none">
                                                    {{ $metric['value'] ?? '' }}
                                                    @if (! empty($metric['unit']))
                                                        <span class="text-xs font-normal text-gray-400 ml-0.5">{{ $metric['unit'] }}</span>
                                                    @endif
                                                </p>
                                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 leading-tight">{{ $metric['label'] ?? '' }}</p>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endif

                        {{-- ── quick_action: clickable shortcuts → AI Chat ─────── --}}
                        @if ($type === 'quick_action' && ! empty($data['shortcuts']))
                            <div class="px-4 pb-3 grid grid-cols-2 sm:grid-cols-3 gap-2">
                                @foreach ($data['shortcuts'] as $shortcut)
                                    <a
                                        href="{{ route('expert-stats.ai.chat', ['send' => $shortcut['query'] ?? $shortcut['label'] ?? '']) }}"
                                        class="flex items-center gap-2 px-3 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 hover:bg-purple-50 hover:border-purple-200 dark:hover:bg-purple-950/30 dark:hover:border-purple-800 transition-colors"
                                    >
                                        <x-heroicon-o-bolt class="w-3.5 h-3.5 text-gray-400 shrink-0" />
                                        <span class="text-xs font-medium text-gray-600 dark:text-gray-300 leading-tight">{{ $shortcut['label'] ?? '' }}</span>
                                    </a>
                                @endforeach
                            </div>
                        @endif

                        {{-- ── Panel footer ─────────────────────────────────────── --}}
                        <div class="mt-auto px-4 py-2.5 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between gap-2 bg-gray-50/50 dark:bg-gray-900/20">
                            <span class="text-xs text-gray-400 dark:text-gray-500">
                                @if (! empty($panel['created_at']))
                                    {{ \Carbon\Carbon::parse($panel['created_at'])->diffForHumans() }}
                                @endif
                            </span>
                            @if (! empty($panel['expires_at']))
                                <span class="text-xs text-gray-400 dark:text-gray-500">
                                    {{ __('expert-statistics::pbx.expert_statistics.ai_dashboard_expires_in') }} {{ \Carbon\Carbon::parse($panel['expires_at'])->diffForHumans(null, true) }}
                                </span>
                            @endif
                        </div>

                    </div>
                @endforeach
            </div>

            {{-- No learning data notice --}}
            @if (isset($meta['has_learning_data']) && ! $meta['has_learning_data'])
                <div class="flex items-center gap-2 px-4 py-2.5 bg-amber-50 border border-amber-200 dark:bg-amber-950/20 dark:border-amber-800 rounded-lg mt-4">
                    <x-heroicon-o-information-circle class="w-4 h-4 text-amber-600 dark:text-amber-400 shrink-0" />
                    <p class="text-xs text-amber-700 dark:text-amber-400">{{ __('expert-statistics::pbx.expert_statistics.ai_dashboard_no_learning_data') }}</p>
                </div>
            @endif
        @endif

    </div>

</div>

</x-pages.index>
