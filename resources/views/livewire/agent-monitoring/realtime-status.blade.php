@php
    $counts = $this->getTotalCounts();
    $alerts = $this->getAlerts();
    $rows = $this->getTableData();
@endphp

<div wire:poll.60s="poll" class="space-y-5">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ __('expert-statistics::pbx.expert_statistics.agent_monitoring_subtitle') }}
        </p>

        <div class="flex items-center gap-3">
            {{-- Live indicator --}}
            <div class="flex items-center gap-1.5">
                <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                    {{ __('expert-statistics::pbx.expert_statistics.agent_monitoring_live') }}
                </span>
                @if ($lastUpdated)
                    <span class="text-xs text-gray-400 dark:text-gray-500">{{ $lastUpdated }}</span>
                @endif
            </div>

            {{-- Alert bell --}}
            <button
                wire:click="toggleAlerts"
                type="button"
                @class([
                    'flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition-colors',
                    'bg-red-100 text-red-700 dark:bg-red-950/40 dark:text-red-400 hover:bg-red-200 dark:hover:bg-red-950/60' => count($alerts) > 0,
                    'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700' => count($alerts) === 0,
                ])
            >
                <x-heroicon-o-bell class="w-4 h-4 {{ count($alerts) > 0 ? 'animate-bounce' : '' }}" />
                <span>{{ __('expert-statistics::pbx.expert_statistics.agent_monitoring_alerts_title') }}</span>
                @if (count($alerts) > 0)
                    <span class="inline-flex items-center justify-center w-4 h-4 bg-red-500 text-white rounded-full text-[10px] font-bold">
                        {{ count($alerts) }}
                    </span>
                @endif
            </button>
        </div>
    </div>

    {{-- Error state --}}
    @if ($errorMessage)
        <div class="rounded-xl bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-900 px-4 py-3 text-sm text-red-700 dark:text-red-400">
            {{ $errorMessage }}
        </div>
    @endif

    {{-- Alert panel --}}
    @if ($showAlerts && count($alerts) > 0)
        <div class="space-y-2">
            @foreach ($alerts as $alert)
                <div @class([
                    'flex items-start justify-between gap-3 rounded-xl px-4 py-3 border-l-4 text-sm',
                    'bg-red-50 dark:bg-red-950/30 border-red-500 text-red-800 dark:text-red-300' => $alert['type'] === 'error' || $alert['severity'] === 'high',
                    'bg-yellow-50 dark:bg-yellow-950/30 border-yellow-500 text-yellow-800 dark:text-yellow-300' => $alert['type'] === 'warning' && $alert['severity'] !== 'high',
                ])>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-semibold">{{ $alert['title'] }}</span>
                            @if ($alert['severity'] === 'high')
                                <span class="inline-flex px-2 py-0.5 text-xs font-semibold bg-red-200 dark:bg-red-900/50 text-red-800 dark:text-red-300 rounded-full">
                                    {{ __('expert-statistics::pbx.expert_statistics.agent_monitoring_high_priority') }}
                                </span>
                            @endif
                        </div>
                        <p class="mt-0.5 text-xs opacity-80">{{ $alert['message'] }}</p>
                    </div>
                    <button
                        wire:click="dismissAlert('{{ $alert['id'] }}')"
                        type="button"
                        class="flex-shrink-0 opacity-50 hover:opacity-100 transition-opacity"
                    >
                        <x-heroicon-m-x-mark class="w-4 h-4" />
                    </button>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Stat cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

        {{-- Available --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                        {{ __('expert-statistics::pbx.expert_statistics.agent_monitoring_agents_online') }}
                    </p>
                    <p class="text-3xl font-bold text-green-600 dark:text-green-400 mt-1">
                        {{ $counts['online'] }}
                    </p>
                </div>
                <div class="p-2.5 bg-green-100 dark:bg-green-950/40 rounded-full">
                    <x-heroicon-o-signal class="w-6 h-6 text-green-600 dark:text-green-400" />
                </div>
            </div>
        </div>

        {{-- Away --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                        {{ __('expert-statistics::pbx.expert_statistics.agent_monitoring_agents_away') }}
                    </p>
                    <p class="text-3xl font-bold text-yellow-500 dark:text-yellow-400 mt-1">
                        {{ $counts['away'] }}
                    </p>
                </div>
                <div class="p-2.5 bg-yellow-100 dark:bg-yellow-950/40 rounded-full">
                    <x-heroicon-o-clock class="w-6 h-6 text-yellow-500 dark:text-yellow-400" />
                </div>
            </div>
        </div>

        {{-- Unavailable --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                        {{ __('expert-statistics::pbx.expert_statistics.agent_monitoring_agents_unavailable') }}
                    </p>
                    <p class="text-3xl font-bold text-red-500 dark:text-red-400 mt-1">
                        {{ $counts['unavailable'] }}
                    </p>
                </div>
                <div class="p-2.5 bg-red-100 dark:bg-red-950/40 rounded-full">
                    <x-heroicon-o-signal-slash class="w-6 h-6 text-red-500 dark:text-red-400" />
                </div>
            </div>
        </div>

        {{-- Total --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                        {{ __('expert-statistics::pbx.expert_statistics.agent_monitoring_total_agents') }}
                    </p>
                    <p class="text-3xl font-bold text-primary-600 dark:text-primary-400 mt-1">
                        {{ $counts['total'] }}
                    </p>
                </div>
                <div class="p-2.5 bg-primary-100 dark:bg-primary-950/40 rounded-full">
                    <x-heroicon-o-users class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                </div>
            </div>
        </div>

    </div>

    {{-- Charts --}}
    @php
        $c1Circum = 326.73; // circumference for r=52
        $c2OuterCircum = 326.73;
        $c2InnerCircum = 238.76; // circumference for r=38

        $connected = count(array_filter(
            $this->agentData,
            fn ($a) => ($a['registration_status']['pbx_registered'] ?? false) === true,
        ));
        $connectedUnavailable = max(0, $connected - $counts['online']);

        // Chart 1 — % of total that are available
        $c1Pct = $counts['total'] > 0 ? round($counts['online'] / $counts['total'] * 100) : 0;
        $c1Dash = round($c1Pct / 100 * $c1Circum, 2);

        // Chart 2 — available vs unavailable among connected agents
        $c2AvailPct = $connected > 0 ? round($counts['online'] / $connected * 100) : 0;
        $c2UnavailPct = $connected > 0 ? round($connectedUnavailable / $connected * 100) : 0;
        $c2AvailDash = round($c2AvailPct / 100 * $c2OuterCircum, 2);
        $c2UnavailDash = round($c2UnavailPct / 100 * $c2InnerCircum, 2);
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        {{-- Chart 1 — Users online --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-5">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-5">
                {{ __('expert-statistics::pbx.expert_statistics.agent_monitoring_chart_online_title') }}
            </h3>

            <div class="flex flex-col items-center gap-4">
                {{-- Radial SVG --}}
                <div class="relative w-40 h-40">
                    <svg viewBox="0 0 120 120" class="w-full h-full -rotate-90">
                        {{-- Track --}}
                        <circle cx="60" cy="60" r="52" fill="none" stroke="currentColor"
                            stroke-width="10" class="text-gray-100 dark:text-gray-700" />
                        {{-- Progress arc --}}
                        <circle cx="60" cy="60" r="52" fill="none" stroke="currentColor"
                            stroke-width="10" stroke-linecap="round"
                            class="text-green-500 dark:text-green-400"
                            stroke-dasharray="{{ $c1Dash }} {{ $c1Circum }}" />
                    </svg>
                    {{-- Centre label --}}
                    <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                        <span class="text-2xl font-bold text-gray-800 dark:text-gray-200 leading-none">
                            {{ $c1Pct }}%
                        </span>
                        <span class="text-[11px] text-gray-400 dark:text-gray-500 mt-1">
                            {{ $connected }}&nbsp;{{ __('expert-statistics::pbx.expert_statistics.agent_monitoring_chart_connected_label') }}
                        </span>
                    </div>
                </div>

                {{-- Legend --}}
                <div class="flex flex-wrap justify-center gap-x-5 gap-y-1.5 text-xs text-gray-500 dark:text-gray-400">
                    <div class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-green-500 dark:bg-green-400 flex-shrink-0"></span>
                        <span>{{ __('expert-statistics::pbx.expert_statistics.agent_monitoring_agents_online') }}
                            &nbsp;<span class="font-semibold text-gray-700 dark:text-gray-300">{{ $counts['online'] }}</span>
                        </span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-gray-300 dark:bg-gray-600 flex-shrink-0"></span>
                        <span>{{ __('expert-statistics::pbx.expert_statistics.agent_monitoring_disconnected') }}
                            &nbsp;<span class="font-semibold text-gray-700 dark:text-gray-300">{{ $counts['total'] - $connected }}</span>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Chart 2 — User status distribution --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-5">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-5">
                {{ __('expert-statistics::pbx.expert_statistics.agent_monitoring_chart_distribution_title') }}
            </h3>

            <div class="flex flex-col items-center gap-4">
                {{-- Concentric radial SVG --}}
                <div class="relative w-40 h-40">
                    <svg viewBox="0 0 120 120" class="w-full h-full -rotate-90">
                        {{-- Outer track (available) --}}
                        <circle cx="60" cy="60" r="52" fill="none" stroke="currentColor"
                            stroke-width="10" class="text-gray-100 dark:text-gray-700" />
                        {{-- Outer progress (available) --}}
                        <circle cx="60" cy="60" r="52" fill="none" stroke="currentColor"
                            stroke-width="10" stroke-linecap="round"
                            class="text-green-500 dark:text-green-400"
                            stroke-dasharray="{{ $c2AvailDash }} {{ $c2OuterCircum }}" />
                        {{-- Inner track (unavailable) --}}
                        <circle cx="60" cy="60" r="38" fill="none" stroke="currentColor"
                            stroke-width="10" class="text-gray-100 dark:text-gray-700" />
                        {{-- Inner progress (unavailable) --}}
                        <circle cx="60" cy="60" r="38" fill="none" stroke="currentColor"
                            stroke-width="10" stroke-linecap="round"
                            class="text-red-500 dark:text-red-400"
                            stroke-dasharray="{{ $c2UnavailDash }} {{ $c2InnerCircum }}" />
                    </svg>
                    {{-- Centre label --}}
                    <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                        <span class="text-2xl font-bold text-gray-800 dark:text-gray-200 leading-none">
                            {{ $connected }}
                        </span>
                        <span class="text-[11px] text-gray-400 dark:text-gray-500 mt-1">
                            {{ __('expert-statistics::pbx.expert_statistics.agent_monitoring_chart_connected_label') }}
                        </span>
                    </div>
                </div>

                {{-- Legend --}}
                <div class="flex flex-wrap justify-center gap-x-5 gap-y-1.5 text-xs text-gray-500 dark:text-gray-400">
                    <div class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-green-500 dark:bg-green-400 flex-shrink-0"></span>
                        <span>{{ __('expert-statistics::pbx.expert_statistics.agent_monitoring_filter_available') }}
                            &nbsp;<span class="font-semibold text-gray-700 dark:text-gray-300">{{ $counts['online'] }}</span>
                            &nbsp;<span class="opacity-60">({{ $c2AvailPct }}%)</span>
                        </span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-red-500 dark:bg-red-400 flex-shrink-0"></span>
                        <span>{{ __('expert-statistics::pbx.expert_statistics.agent_monitoring_filter_unavailable') }}
                            &nbsp;<span class="font-semibold text-gray-700 dark:text-gray-300">{{ $connectedUnavailable }}</span>
                            &nbsp;<span class="opacity-60">({{ $c2UnavailPct }}%)</span>
                        </span>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- Filters + Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden">

        {{-- Filter bar --}}
        <div class="px-5 py-4 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
            <div class="flex flex-wrap items-center gap-3">
                {{-- Search --}}
                <div class="relative min-w-48 flex-1">
                    <x-heroicon-m-magnifying-glass class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" />
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="searchTerm"
                        placeholder="{{ __('expert-statistics::pbx.expert_statistics.agent_monitoring_search_placeholder') }}"
                        class="w-full pl-9 pr-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                    />
                </div>

                {{-- Status filter --}}
                <select
                    wire:model.live="statusFilter"
                    class="py-2 pl-3 pr-8 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                >
                    <option value="">{{ __('expert-statistics::pbx.expert_statistics.agent_monitoring_all_status') }}</option>
                    <option value="available">{{ __('expert-statistics::pbx.expert_statistics.agent_monitoring_filter_available') }}</option>
                    <option value="away">{{ __('expert-statistics::pbx.expert_statistics.agent_monitoring_filter_away') }}</option>
                    <option value="unavailable">{{ __('expert-statistics::pbx.expert_statistics.agent_monitoring_filter_unavailable') }}</option>
                </select>

                {{-- Registration filter --}}
                <select
                    wire:model.live="registrationFilter"
                    class="py-2 pl-3 pr-8 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                >
                    <option value="">{{ __('expert-statistics::pbx.expert_statistics.agent_monitoring_all_registration') }}</option>
                    <option value="true">{{ __('expert-statistics::pbx.expert_statistics.agent_monitoring_connected') }}</option>
                    <option value="false">{{ __('expert-statistics::pbx.expert_statistics.agent_monitoring_disconnected') }}</option>
                </select>
            </div>
        </div>

        {{-- Loading overlay --}}
        <div wire:loading.flex wire:target="sortBy,searchTerm,statusFilter,registrationFilter,poll"
            class="items-center justify-center py-8">
            <svg class="w-6 h-6 text-primary-500 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
            </svg>
        </div>

        {{-- Table --}}
        <div wire:loading.remove wire:target="sortBy,searchTerm,statusFilter,registrationFilter,poll"
            class="overflow-x-auto">

            @if (! $errorMessage && empty($agentData))
                {{-- No data state --}}
                <div class="px-5 py-12 text-center">
                    <x-heroicon-o-users class="w-10 h-10 mx-auto text-gray-200 dark:text-gray-700 mb-3" />
                    <p class="text-sm text-gray-400 dark:text-gray-500">
                        {{ __('expert-statistics::pbx.expert_statistics.agent_monitoring_no_data') }}
                    </p>
                </div>

            @elseif (count($rows) === 0)
                {{-- Empty search/filter result --}}
                <div class="px-5 py-12 text-center">
                    <x-heroicon-o-magnifying-glass class="w-10 h-10 mx-auto text-gray-200 dark:text-gray-700 mb-3" />
                    <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-400 mb-1">
                        {{ __('expert-statistics::pbx.expert_statistics.agent_monitoring_no_agents') }}
                    </h3>
                    <p class="text-xs text-gray-400 dark:text-gray-500">
                        {{ __('expert-statistics::pbx.expert_statistics.agent_monitoring_no_agents_hint') }}
                    </p>
                </div>

            @else
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            {{-- Agent --}}
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                {{ __('expert-statistics::pbx.expert_statistics.agent_monitoring_col_agent') }}
                            </th>

                            {{-- Connection --}}
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                {{ __('expert-statistics::pbx.expert_statistics.agent_monitoring_col_registration') }}
                            </th>

                            {{-- Connected for (sortable) --}}
                            <th
                                wire:click="sortBy('registered_duration')"
                                class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700/50 select-none"
                            >
                                <div class="flex items-center gap-1">
                                    <span>{{ __('expert-statistics::pbx.expert_statistics.agent_monitoring_col_registration_duration') }}</span>
                                    @if ($sortKey === 'registered_duration')
                                        @if ($sortDesc)
                                            <x-heroicon-m-chevron-down class="w-3.5 h-3.5" />
                                        @else
                                            <x-heroicon-m-chevron-up class="w-3.5 h-3.5" />
                                        @endif
                                    @else
                                        <x-heroicon-m-chevron-up-down class="w-3.5 h-3.5 opacity-40" />
                                    @endif
                                </div>
                            </th>

                            {{-- Status --}}
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                {{ __('expert-statistics::pbx.expert_statistics.agent_monitoring_col_status') }}
                            </th>

                            {{-- Duration (sortable) --}}
                            <th
                                wire:click="sortBy('status_duration')"
                                class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700/50 select-none"
                            >
                                <div class="flex items-center gap-1">
                                    <span>{{ __('expert-statistics::pbx.expert_statistics.agent_monitoring_col_duration') }}</span>
                                    @if ($sortKey === 'status_duration')
                                        @if ($sortDesc)
                                            <x-heroicon-m-chevron-down class="w-3.5 h-3.5" />
                                        @else
                                            <x-heroicon-m-chevron-up class="w-3.5 h-3.5" />
                                        @endif
                                    @else
                                        <x-heroicon-m-chevron-up-down class="w-3.5 h-3.5 opacity-40" />
                                    @endif
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                        @foreach ($rows as $agent)
                            @php
                                $dn = (string) ($agent['user_dn'] ?? '');
                                $registered = $agent['registration_status']['pbx_registered'] ?? false;
                                $statusCode = (int) ($agent['current_status']['code'] ?? -1);
                                $statusName = (string) ($agent['current_status']['name'] ?? '');
                                $regMinutes = (int) ($agent['registration_status']['duration']['minutes'] ?? 0);
                                $statusMinutes = (int) ($agent['status_duration']['minutes'] ?? 0);
                                $regFormatted = (string) ($agent['registration_status']['duration']['formatted'] ?? $this->formatDuration($regMinutes));
                                $statusFormatted = (string) ($agent['status_duration']['formatted'] ?? $this->formatDuration($statusMinutes));
                            @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">

                                {{-- Agent ID + dot --}}
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full flex-shrink-0 {{ $this->getStatusDotClass($agent) }}"></span>
                                        <span class="font-medium text-gray-800 dark:text-gray-200">
                                            {{ $this->getAgentLabel($dn) }}
                                        </span>
                                    </div>
                                </td>

                                {{-- Registration badge --}}
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold {{ $this->getRegistrationBadgeClass($agent) }}">
                                        {{ $registered
                                            ? __('expert-statistics::pbx.expert_statistics.agent_monitoring_connected')
                                            : __('expert-statistics::pbx.expert_statistics.agent_monitoring_disconnected') }}
                                    </span>
                                </td>

                                {{-- Registration duration --}}
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $registered ? 'bg-green-50 text-green-700 dark:bg-green-950/30 dark:text-green-400' : 'bg-red-50 text-red-700 dark:bg-red-950/30 dark:text-red-400' }}">
                                        {{ $regFormatted ?: $this->formatDuration($regMinutes) }}
                                    </span>
                                </td>

                                {{-- Status badge --}}
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold {{ $this->getStatusBadgeClass($agent) }}">
                                        @if (! $registered)
                                            {{ __('expert-statistics::pbx.expert_statistics.agent_monitoring_disconnected') }}
                                        @elseif ($statusCode === 0)
                                            {{ __('expert-statistics::pbx.expert_statistics.agent_monitoring_available') }}
                                        @else
                                            {{ $statusName ?: __('expert-statistics::pbx.expert_statistics.agent_monitoring_filter_unavailable') }}
                                        @endif
                                    </span>
                                </td>

                                {{-- Status duration --}}
                                <td class="px-5 py-3.5 whitespace-nowrap text-gray-600 dark:text-gray-400">
                                    {{ $statusFormatted ?: $this->formatDuration($statusMinutes) }}
                                </td>

                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

</div>
