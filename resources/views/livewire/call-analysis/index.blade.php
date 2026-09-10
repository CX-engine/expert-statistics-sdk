<x-pages.index
    :title="__('expert-statistics::pbx.expert_statistics.nav_call_analysis')"
    :subtitle="__('expert-statistics::pbx.expert_statistics.home_feature_call_details_desc')"
>

<div class="space-y-5">

    {{-- Date / Time filter bar --}}
    <x-expert-statistics::filter-bar
        :selectedPeriod="$selectedPeriod"
        :startDate="$startDate"
        :endDate="$endDate"
        :startTime="$startTime"
        :endTime="$endTime"
        :showTimeRange="true"
    />

    {{-- 4-section filter panel --}}
    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 divide-y xl:divide-y-0 xl:divide-x divide-gray-100 dark:divide-gray-800">

            {{-- Section 1: Origin --}}
            <div class="p-4 space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-1.5">
                        <x-heroicon-o-phone-arrow-down-left class="w-4 h-4 text-gray-400 dark:text-gray-500 shrink-0" />
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('expert-statistics::pbx.expert_statistics.cfa_origin_title') }}
                        </span>
                    </div>
                    @if (! empty($selectedOriginDns))
                        <span class="tabular-nums text-[10px] font-semibold text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-950/50 px-1.5 py-0.5 rounded-full ring-1 ring-primary-200 dark:ring-primary-800">
                            {{ count($selectedOriginDns) }}
                        </span>
                    @endif
                </div>

                <div class="flex rounded-lg bg-gray-100 dark:bg-gray-800/70 p-0.5 gap-0.5">
                    @foreach (['' => __('expert-statistics::pbx.expert_statistics.cfa_type_all'), '4' => __('expert-statistics::pbx.expert_statistics.cfa_type_queue'), '0' => __('expert-statistics::pbx.expert_statistics.cfa_type_user')] as $val => $segLabel)
                    <button
                        type="button"
                        wire:click="$set('originDnType', '{{ $val }}')"
                        class="{{ $originDnType === $val
                            ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm'
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200' }}
                            flex-1 rounded-md px-2 py-1.5 text-xs font-medium transition-all"
                    >{{ $segLabel }}</button>
                    @endforeach
                </div>

                @if ($originDnType === '4')
                    <x-expert-statistics::call-flow-dn-selector
                        :elements="$queuesReduced"
                        selectedProp="selectedOriginDns"
                        :placeholder="__('expert-statistics::pbx.expert_statistics.selector_placeholder_queue')"
                        wireAdd="addOriginDn"
                        wireRemove="elementRemovedOrigin"
                    />
                @elseif ($originDnType === '0')
                    <x-expert-statistics::call-flow-dn-selector
                        :elements="$extensionsReduced"
                        selectedProp="selectedOriginDns"
                        :placeholder="__('expert-statistics::pbx.expert_statistics.selector_placeholder_extension')"
                        wireAdd="addOriginDn"
                        wireRemove="elementRemovedOrigin"
                    />
                @else
                    <p class="text-xs text-gray-400 dark:text-gray-500 italic leading-relaxed">
                        {{ __('expert-statistics::pbx.expert_statistics.cfa_type_all_hint') }}
                    </p>
                @endif
            </div>

            {{-- Section 2: Destination --}}
            <div class="p-4 space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-1.5">
                        <x-heroicon-o-phone-arrow-up-right class="w-4 h-4 text-gray-400 dark:text-gray-500 shrink-0" />
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('expert-statistics::pbx.expert_statistics.cfa_destination_title') }}
                        </span>
                    </div>
                    @if (! empty($selectedDestinationDns))
                        <span class="tabular-nums text-[10px] font-semibold text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-950/50 px-1.5 py-0.5 rounded-full ring-1 ring-primary-200 dark:ring-primary-800">
                            {{ count($selectedDestinationDns) }}
                        </span>
                    @endif
                </div>

                <div class="flex rounded-lg bg-gray-100 dark:bg-gray-800/70 p-0.5 gap-0.5">
                    @foreach (['' => __('expert-statistics::pbx.expert_statistics.cfa_type_all'), '4' => __('expert-statistics::pbx.expert_statistics.cfa_type_queue'), '0' => __('expert-statistics::pbx.expert_statistics.cfa_type_user')] as $val => $segLabel)
                    <button
                        type="button"
                        wire:click="$set('destinationDnType', '{{ $val }}')"
                        class="{{ $destinationDnType === $val
                            ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm'
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200' }}
                            flex-1 rounded-md px-2 py-1.5 text-xs font-medium transition-all"
                    >{{ $segLabel }}</button>
                    @endforeach
                </div>

                @if ($destinationDnType === '4')
                    <x-expert-statistics::call-flow-dn-selector
                        :elements="$queuesReduced"
                        selectedProp="selectedDestinationDns"
                        :placeholder="__('expert-statistics::pbx.expert_statistics.selector_placeholder_queue')"
                        wireAdd="addDestinationDn"
                        wireRemove="elementRemovedDestination"
                    />
                @elseif ($destinationDnType === '0')
                    <x-expert-statistics::call-flow-dn-selector
                        :elements="$extensionsReduced"
                        selectedProp="selectedDestinationDns"
                        :placeholder="__('expert-statistics::pbx.expert_statistics.selector_placeholder_extension')"
                        wireAdd="addDestinationDn"
                        wireRemove="elementRemovedDestination"
                    />
                @else
                    <p class="text-xs text-gray-400 dark:text-gray-500 italic leading-relaxed">
                        {{ __('expert-statistics::pbx.expert_statistics.cfa_type_all_hint') }}
                    </p>
                @endif
            </div>

            {{-- Section 3: Numbers (DID + Caller) --}}
            <div class="p-4 space-y-4">

                {{-- DID sub-section --}}
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-1.5">
                            <x-heroicon-o-hashtag class="w-4 h-4 text-gray-400 dark:text-gray-500 shrink-0" />
                            <span class="text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('expert-statistics::pbx.expert_statistics.cfa_did_title') }}
                            </span>
                        </div>
                        @if (! empty($selectedDidNumbers))
                            <span class="tabular-nums text-[10px] font-semibold text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-950/50 px-1.5 py-0.5 rounded-full ring-1 ring-primary-200 dark:ring-primary-800">
                                {{ count($selectedDidNumbers) }}
                            </span>
                        @endif
                    </div>
                    <x-expert-statistics::call-flow-dn-selector
                        :elements="$didsReduced"
                        selectedProp="selectedDidNumbers"
                        :placeholder="__('expert-statistics::pbx.expert_statistics.selector_placeholder_did')"
                        wireAdd="addDidNumber"
                        wireRemove="elementRemovedDid"
                    />
                </div>

                {{-- Caller sub-section --}}
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-1.5">
                            <x-heroicon-o-user class="w-4 h-4 text-gray-400 dark:text-gray-500 shrink-0" />
                            <span class="text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('expert-statistics::pbx.expert_statistics.cfa_caller_title') }}
                            </span>
                        </div>
                        @if (! empty($selectedCallerNumbers))
                            <span class="tabular-nums text-[10px] font-semibold text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-950/50 px-1.5 py-0.5 rounded-full ring-1 ring-primary-200 dark:ring-primary-800">
                                {{ count($selectedCallerNumbers) }}
                            </span>
                        @endif
                    </div>

                    {{-- Caller live-search input --}}
                    <div x-data="{ open: false }" class="relative space-y-2">
                        <div class="relative">
                            <div class="pointer-events-none absolute inset-y-0 left-3 flex items-center">
                                <x-heroicon-o-magnifying-glass class="w-3.5 h-3.5 text-gray-400" />
                            </div>
                            <input
                                type="text"
                                wire:model.live.debounce.500ms="callerSearch"
                                @focus="open = true"
                                @click.away="open = false"
                                placeholder="{{ __('expert-statistics::pbx.expert_statistics.cfa_caller_search_placeholder') }}"
                                class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-sm pl-9 pr-8 py-2 text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition"
                            />
                            @if ($callerSearch)
                                <button
                                    wire:click="$set('callerSearch', '')"
                                    type="button"
                                    class="absolute inset-y-0 right-2.5 flex items-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition"
                                >
                                    <x-heroicon-m-x-mark class="w-3.5 h-3.5" />
                                </button>
                            @endif
                        </div>

                        <div
                            x-show="open && $wire.callerSearchResults.length > 0"
                            x-transition.opacity
                            class="absolute left-0 top-full mt-1 z-50 w-full bg-white dark:bg-gray-900 rounded-xl shadow-xl ring-1 ring-gray-950/10 dark:ring-white/10 overflow-hidden"
                        >
                            <div class="max-h-44 overflow-y-auto divide-y divide-gray-50 dark:divide-gray-800">
                                <template x-for="item in $wire.callerSearchResults" :key="JSON.stringify(item.value)">
                                    <label
                                        class="flex items-center gap-2.5 px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-800/60 cursor-pointer transition"
                                        :class="item.isConstructor ? 'bg-gray-50 dark:bg-gray-800/40' : ''"
                                    >
                                        <input
                                            type="checkbox"
                                            :checked="Array.isArray(item.value)
                                                ? item.value.some(v => $wire.selectedCallerNumbers.includes(String(v)))
                                                : $wire.selectedCallerNumbers.includes(String(item.value))"
                                            @change="$wire.addCallerNumber(item); open = false"
                                            class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500"
                                        />
                                        <span class="flex-1 min-w-0">
                                            <span
                                                x-text="item.label"
                                                :class="item.isConstructor
                                                    ? 'block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400'
                                                    : 'text-sm text-gray-700 dark:text-gray-300'"
                                            ></span>
                                            <template x-if="item.isConstructor && Array.isArray(item.value)">
                                                <span class="text-xs text-gray-400 dark:text-gray-500" x-text="item.value.length + ' {{ __('expert-statistics::pbx.config.groups.members') }}'"></span>
                                            </template>
                                        </span>
                                    </label>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Section 4: Flow & Result --}}
            <div class="p-4 space-y-4">

                {{-- Call direction --}}
                <div class="space-y-2">
                    <div class="flex items-center gap-1.5">
                        <x-heroicon-o-arrows-right-left class="w-4 h-4 text-gray-400 dark:text-gray-500 shrink-0" />
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('expert-statistics::pbx.expert_statistics.cfa_call_way_title') }}
                        </span>
                    </div>
                    <div class="grid grid-cols-2 gap-1">
                        @foreach ([
                            ''         => ['label' => __('expert-statistics::pbx.expert_statistics.cfa_call_way_all'),      'icon' => null],
                            'inbound'  => ['label' => __('expert-statistics::pbx.expert_statistics.cfa_call_way_inbound'),  'icon' => 'heroicon-m-arrow-down-left'],
                            'outbound' => ['label' => __('expert-statistics::pbx.expert_statistics.cfa_call_way_outbound'), 'icon' => 'heroicon-m-arrow-up-right'],
                            'internal' => ['label' => __('expert-statistics::pbx.expert_statistics.cfa_call_way_internal'), 'icon' => 'heroicon-m-arrows-right-left'],
                        ] as $val => $opt)
                        <button
                            type="button"
                            wire:click="$set('callWay', '{{ $val }}')"
                            class="{{ $callWay === $val
                                ? 'bg-primary-50 dark:bg-primary-950/40 text-primary-700 dark:text-primary-300 ring-1 ring-primary-200 dark:ring-primary-800'
                                : 'bg-gray-50 dark:bg-gray-800/60 text-gray-500 dark:text-gray-400 ring-1 ring-gray-200 dark:ring-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-700 dark:hover:text-gray-200' }}
                                inline-flex items-center justify-center gap-1.5 rounded-lg px-2 py-1.5 text-xs font-medium transition-all"
                        >
                            @if ($opt['icon'])
                                <x-dynamic-component :component="$opt['icon']" class="w-3 h-3 shrink-0" />
                            @endif
                            {{ $opt['label'] }}
                        </button>
                        @endforeach
                    </div>
                </div>

                {{-- Call status --}}
                <div class="space-y-2">
                    <div class="flex items-center gap-1.5">
                        <x-heroicon-o-check-circle class="w-4 h-4 text-gray-400 dark:text-gray-500 shrink-0" />
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('expert-statistics::pbx.expert_statistics.cfa_call_status_title') }}
                        </span>
                    </div>
                    <div class="flex rounded-lg bg-gray-100 dark:bg-gray-800/70 p-0.5 gap-0.5">
                        @foreach (['all' => __('expert-statistics::pbx.expert_statistics.cfa_call_status_all'), 'answered' => __('expert-statistics::pbx.expert_statistics.cfa_call_status_answered'), 'unanswered' => __('expert-statistics::pbx.expert_statistics.cfa_call_status_unanswered')] as $val => $segLabel)
                        <button
                            type="button"
                            wire:click="$set('callStatus', '{{ $val }}')"
                            class="{{ $callStatus === $val
                                ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm'
                                : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200' }}
                                flex-1 rounded-md px-2 py-1.5 text-xs font-medium transition-all"
                        >{{ $segLabel }}</button>
                        @endforeach
                    </div>
                </div>

                {{-- Exclude closed hours toggle --}}
                <div class="space-y-2">
                    <div class="flex items-center gap-1.5">
                        <x-heroicon-o-clock class="w-4 h-4 text-gray-400 dark:text-gray-500 shrink-0" />
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('expert-statistics::pbx.expert_statistics.exclude_closed_hours') }}
                        </span>
                    </div>
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" wire:model.live="excludeClosedHours"
                            class="rounded border-gray-300 dark:border-gray-600 text-primary-600" />
                        <span class="text-sm text-gray-600 dark:text-gray-400">{{
                            __('expert-statistics::pbx.expert_statistics.exclude_closed_hours') }}</span>
                    </label>
                </div>
            </div>
        </div>

        {{-- Action bar --}}
        <div class="flex items-center justify-between px-5 py-3.5 border-t border-gray-100 dark:border-gray-800 bg-gray-50/60 dark:bg-gray-800/30 rounded-b-2xl">
            <button
                wire:click="resetFilters"
                wire:loading.attr="disabled"
                wire:loading.class="opacity-50 cursor-not-allowed"
                wire:target="resetFilters,applyFilters"
                type="button"
                class="inline-flex items-center gap-1.5 px-3.5 py-2 text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 rounded-xl hover:bg-white dark:hover:bg-gray-800 border border-transparent hover:border-gray-200 dark:hover:border-gray-700 transition"
            >
                <span wire:loading.remove wire:target="resetFilters" class="contents">
                    <x-heroicon-m-arrow-path class="w-3.5 h-3.5" />
                </span>
                <svg wire:loading wire:target="resetFilters" class="animate-spin w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                {{ __('expert-statistics::pbx.expert_statistics.cfa_reset') }}
            </button>

            <button
                wire:click="applyFilters"
                wire:loading.attr="disabled"
                wire:loading.class="opacity-75 cursor-not-allowed"
                wire:target="applyFilters"
                type="button"
                class="inline-flex items-center gap-2 px-5 py-2 text-sm font-semibold text-white bg-primary-600 hover:bg-primary-700 rounded-xl shadow-sm transition"
            >
                <span wire:loading.remove wire:target="applyFilters" class="contents">
                    <x-heroicon-m-funnel class="w-4 h-4" />
                </span>
                <svg wire:loading wire:target="applyFilters" class="animate-spin w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                {{ __('expert-statistics::pbx.expert_statistics.cfa_apply') }}
            </button>
        </div>
    </div>

    {{-- Results area --}}
    <div class="relative">

        <div
            wire:loading.delay
            wire:target="applyFilters,changePage"
            class="absolute inset-0 z-20 flex items-start justify-center pt-20 bg-white/75 dark:bg-gray-950/75 backdrop-blur-[1px] rounded-xl min-h-32"
        >
            <div class="flex items-center gap-3 bg-white dark:bg-gray-800 shadow-lg rounded-xl px-5 py-3 ring-1 ring-gray-200 dark:ring-gray-700">
                <svg class="animate-spin w-4 h-4 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('expert-statistics::pbx.dashboards.loading') }}</span>
            </div>
        </div>

    @if (! $filtersApplied)
        {{-- Empty state A: no filters applied yet --}}
        <div class="text-center max-w-lg mx-auto space-y-4 py-14">
            <x-heroicon-o-magnifying-glass class="mx-auto h-12 w-12 text-teal-500 dark:text-teal-400" />
            <h2 class="text-gray-900 dark:text-white font-bold text-xl">
                {{ __('expert-statistics::pbx.expert_statistics.cfa_empty_title') }}
            </h2>
            <p class="text-gray-600 dark:text-gray-400 text-sm leading-relaxed">
                {!! __('expert-statistics::pbx.expert_statistics.cfa_empty_description') !!}
            </p>
            <p class="text-gray-400 dark:text-gray-500 text-sm italic pt-2">
                {{ __('expert-statistics::pbx.expert_statistics.cfa_empty_hint') }}
            </p>
        </div>

    @elseif (empty($calls))
        {{-- Empty state B: filters applied but no results --}}
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 px-6 py-10 text-center">
            <x-heroicon-o-inbox class="mx-auto h-10 w-10 text-gray-300 dark:text-gray-600" />
            <p class="mt-3 text-sm font-medium text-gray-500 dark:text-gray-400">
                {{ __('expert-statistics::pbx.expert_statistics.no_data') }}
            </p>
        </div>

    @else
        {{-- Results header --}}
        <div class="flex items-center justify-between">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('expert-statistics::pbx.expert_statistics.cfa_results_count', ['total' => $pagination['total'], 'from' => (($pagination['current_page'] - 1) * $pagination['per_page']) + 1, 'to' => min($pagination['current_page'] * $pagination['per_page'], $pagination['total'])]) }}
            </p>

            <x-expert-statistics::export-button :url="$this->getExportUrl()" />
        </div>

        {{-- Call accordion cards --}}
        <div class="space-y-2">
            @foreach ($calls as $call)
            @php
                $direction = $call['direction'] ?? '';
                $callerDn  = $call['caller'] ?? '';
                $did       = $call['did'] ?? '';
                $flow      = $call['flow'] ?? [];

                $dirColor = match ($direction) {
                    'inbound', 'transfer'  => 'text-green-600 dark:text-green-400',
                    'outbound', 'unknown'  => 'text-blue-600 dark:text-blue-400',
                    'internal'             => 'text-purple-600 dark:text-purple-400',
                    default                => 'text-gray-500 dark:text-gray-400',
                };

                $pingSteps = array_values(array_filter($flow, fn ($s) => ($s['segment_type'] ?? '') === 'ping'));

                $pingStartedAts = array_filter(array_column($pingSteps, 'started_at'));
                $pingEndedAts   = array_filter(array_column($pingSteps, 'ended_at'));
                $displayStart   = ! empty($pingStartedAts) ? min($pingStartedAts) : ($call['start'] ?? null);
                $displayEnd     = ! empty($pingEndedAts)   ? max($pingEndedAts)   : ($call['end'] ?? null);

                $duration = ($displayStart && $displayEnd)
                    ? $this->formatDuration($displayStart, $displayEnd)
                    : '—';

                $flowPreview = $this->buildFlowPreview($flow);

                $isAnswered = false;
                $destDn = ''; $destType = ''; $destName = null;
                foreach ($flow as $_fs) {
                    if (! empty($_fs['answered_at']) && ($_fs['to_type'] ?? '') === 'extension') {
                        $isAnswered = true;
                    }
                    if (($_fs['segment_type'] ?? '') === 'ping' && ! empty($_fs['to_dn'])) {
                        $destDn   = $_fs['to_dn'];
                        $destType = $_fs['to_type'] ?? '';
                        $destName = $_fs['to_name'] ?? null;
                    }
                }

                $callerDisplay = in_array($direction, ['internal', 'outbound', 'unknown'])
                    ? ($pbxMap['extensions'][$callerDn] ?? $callerDn)
                    : $callerDn;

                $callerCompanyName = ($callerDisplay === $callerDn) ? ($callerGroupMap[$callerDn] ?? null) : null;

                $destDisplay = $this->resolveStepName($destDn, $destType, $destName);

                $typeLabels = [
                    'extension' => 'Extension',
                    'queue'     => 'Queue',
                    'voicemail' => 'Voicemail',
                    'ivr'       => 'IVR',
                    'call_flow' => 'Call Flow',
                ];
            @endphp

            <div
                x-data="{ open: false }"
                class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden"
            >
                {{-- Card header --}}
                <div class="px-5 py-4 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800/50 transition" @click="open = !open">

                    <div class="flex flex-wrap items-center gap-3">

                        <div class="shrink-0 {{ $dirColor }}">
                            @if (in_array($direction, ['inbound', 'transfer']))
                                <x-heroicon-o-phone-arrow-down-left class="w-5 h-5" />
                            @elseif (in_array($direction, ['outbound', 'unknown']))
                                <x-heroicon-o-phone-arrow-up-right class="w-5 h-5" />
                            @else
                                <x-heroicon-o-arrows-right-left class="w-5 h-5" />
                            @endif
                        </div>

                        <div class="flex items-center gap-2 min-w-0 flex-1">
                            <span class="text-sm font-semibold text-gray-800 dark:text-gray-200 tabular-nums">
                                {{ $callerDisplay ?: '—' }}
                                @if ($callerDisplay && $callerDisplay !== $callerDn && $callerDn)
                                    <span class="text-xs text-gray-400 dark:text-gray-500 font-mono">({{ $callerDn }})</span>
                                @elseif ($callerCompanyName)
                                    <span class="text-xs text-gray-400 dark:text-gray-500">({{ $callerCompanyName }})</span>
                                @endif
                            </span>
                            <x-heroicon-m-arrow-right class="w-3 h-3 text-gray-400 shrink-0" />
                            <span class="text-sm text-gray-600 dark:text-gray-400 truncate">
                                {{ $destDisplay ?: '—' }}
                            </span>
                        </div>

                        @if ($did)
                            <span class="inline-flex items-center gap-1 text-xs text-indigo-700 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-950/40 px-2 py-0.5 rounded-full ring-1 ring-indigo-200 dark:ring-indigo-800 shrink-0">
                                <x-heroicon-m-hashtag class="w-3 h-3 shrink-0" />
                                {{ $did }}
                            </span>
                        @endif

                        @if ($displayStart)
                            <span class="text-xs text-gray-400 dark:text-gray-500 tabular-nums shrink-0">
                                {{ \Carbon\Carbon::parse($displayStart)->format('d/m H:i') }}
                                @if ($displayEnd)→ {{ \Carbon\Carbon::parse($displayEnd)->format('H:i') }}@endif
                            </span>
                        @endif

                        <span class="inline-flex items-center gap-1 text-xs font-medium text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded-full tabular-nums shrink-0">
                            <x-heroicon-m-clock class="w-3 h-3" />
                            {{ $duration }}
                        </span>

                        @if ($isAnswered)
                            <span class="inline-flex items-center gap-1 text-xs font-medium text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-950/50 px-2 py-0.5 rounded-full ring-1 ring-green-200 dark:ring-green-800 shrink-0">
                                <x-heroicon-m-check class="w-3 h-3" />
                                {{ __('expert-statistics::pbx.expert_statistics.cfa_answered') }}
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 text-xs font-medium text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-950/50 px-2 py-0.5 rounded-full ring-1 ring-red-200 dark:ring-red-800 shrink-0">
                                <x-heroicon-m-x-mark class="w-3 h-3" />
                                {{ __('expert-statistics::pbx.expert_statistics.cfa_unanswered') }}
                            </span>
                        @endif

                        <x-heroicon-m-chevron-down class="w-4 h-4 text-gray-400 shrink-0 ml-auto transition-transform" x-bind:class="open ? 'rotate-180' : ''" />
                    </div>

                    {{-- Flow preview chips --}}
                    @if (! empty($flowPreview))
                    <div class="mt-3 flex flex-wrap items-center gap-1.5">
                        @foreach ($flowPreview as $fpIdx => $fpStep)
                            @if ($fpIdx > 0)
                                <x-heroicon-m-arrow-right class="w-3 h-3 text-gray-300 dark:text-gray-600 shrink-0" />
                            @endif
                            @php
                                $fpColor = $fpStep['answered']
                                    ? 'bg-green-100 dark:bg-green-950/50 text-green-700 dark:text-green-300 ring-green-200 dark:ring-green-800'
                                    : 'bg-red-100 dark:bg-red-950/50 text-red-700 dark:text-red-300 ring-red-200 dark:ring-red-800';
                                $fpToDn   = $fpStep['to_dn'] ?? '';
                                $fpToType = $fpStep['to_type'] ?? '';
                                $fpLabel  = $fpStep['to_name'] ?? null;
                                if (! $fpLabel && $fpToDn) {
                                    if ($fpToType === 'extension') {
                                        $fpLabel = $pbxMap['extensions'][$fpToDn] ?? $fpToDn;
                                    } elseif ($fpToType === 'queue') {
                                        $fpq = $pbxMap['call_queues'][$fpToDn] ?? null;
                                        $fpLabel = (is_array($fpq) ? ($fpq['name'] ?? null) : null) ?? $fpToDn;
                                    } else {
                                        $fpLabel = $fpToDn ?: '?';
                                    }
                                }
                            @endphp
                            <span class="inline-flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded-full ring-1 {{ $fpColor }}">
                                @if ($fpToType === 'queue')
                                    <x-heroicon-m-queue-list class="w-3 h-3 shrink-0" />
                                @elseif ($fpToType === 'extension')
                                    <x-heroicon-m-user class="w-3 h-3 shrink-0" />
                                @elseif (in_array($fpToType, ['ivr', 'call_flow']))
                                    <x-heroicon-m-adjustments-horizontal class="w-3 h-3 shrink-0" />
                                @elseif ($fpToType === 'voicemail')
                                    <x-heroicon-m-envelope class="w-3 h-3 shrink-0" />
                                @else
                                    <x-heroicon-m-phone class="w-3 h-3 shrink-0" />
                                @endif
                                <span>{{ $fpLabel }}</span>
                                @if ($fpStep['count'] > 1)
                                    <span class="opacity-60">×{{ $fpStep['count'] }}</span>
                                @endif
                                @if (($fpStep['total_secs'] ?? 0) > 0)
                                    <span class="opacity-50 tabular-nums">· {{ $this->formatSecondsShort($fpStep['total_secs']) }}</span>
                                @endif
                            </span>
                        @endforeach
                    </div>
                    @endif
                </div>

                {{-- Expanded timeline (ping segments only) --}}
                <div x-show="open" x-transition class="border-t border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/20">
                    <div class="p-4">
                        <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-4">
                            {{ __('expert-statistics::pbx.expert_statistics.cfa_timeline_title') }}
                        </h4>

                        @if (empty($pingSteps))
                            <p class="text-sm text-gray-400 dark:text-gray-500 italic">—</p>
                        @else
                        <div>
                            @foreach ($pingSteps as $stIdx => $step)
                            @php
                                $answeredAt   = $step['answered_at'] ?? null;
                                $stepAnswered = ! empty($answeredAt);
                                $stepSecs     = (int) ($step['duration'] ?? 0);
                                $isLastStep   = $stIdx === count($pingSteps) - 1;

                                $fromType = $step['from_type'] ?? '';
                                $fromDn   = $step['from_dn'] ?? '';
                                $fromName = $step['from_name'] ?? null;
                                $toType   = $step['to_type'] ?? '';
                                $toDn     = $step['to_dn'] ?? '';
                                $toName   = $step['to_name'] ?? null;
                                $action   = $step['action'] ?? '';
                                $segmentLabelKey = $this->getSegmentLabel($step['segment_type'] ?? '', $stepAnswered);

                                if ($fromType === 'world' && $toType === 'world' && $callerDn) {
                                    $fromDisplay = $callerDn;
                                } elseif ($fromType === 'world' && $did) {
                                    $fromDisplay = $did;
                                } elseif ($fromName) {
                                    $fromDisplay = $fromName;
                                } elseif ($fromDn) {
                                    if ($fromType === 'extension') {
                                        $n = $pbxMap['extensions'][$fromDn] ?? null;
                                        $fromDisplay = $n ? "{$n} ({$fromDn})" : $fromDn;
                                    } elseif ($fromType === 'queue') {
                                        $fq = $pbxMap['call_queues'][$fromDn] ?? null;
                                        $fn = is_array($fq) ? ($fq['name'] ?? null) : null;
                                        $fromDisplay = $fn ? "{$fn} ({$fromDn})" : $fromDn;
                                    } else {
                                        $fromDisplay = $fromDn;
                                    }
                                } else {
                                    $fromDisplay = '—';
                                }
                                $fromTypeLabel = ($fromType && $fromType !== 'world') ? ($typeLabels[$fromType] ?? null) : null;

                                if ($toName) {
                                    $toDisplay = $toName;
                                } elseif ($toDn) {
                                    if ($toType === 'extension') {
                                        $n = $pbxMap['extensions'][$toDn] ?? null;
                                        $toDisplay = $n ? "{$n} ({$toDn})" : $toDn;
                                    } elseif ($toType === 'queue') {
                                        $tq = $pbxMap['call_queues'][$toDn] ?? null;
                                        $tn = is_array($tq) ? ($tq['name'] ?? null) : null;
                                        $toDisplay = $tn ? "{$tn} ({$toDn})" : $toDn;
                                    } else {
                                        $toDisplay = $toDn;
                                    }
                                } else {
                                    $toDisplay = '—';
                                }
                                $toTypeLabel = ($toType && $toType !== 'world') ? ($typeLabels[$toType] ?? null) : null;

                                $nodeColor  = $stepAnswered ? 'bg-green-500 dark:bg-green-400' : 'bg-red-400 dark:bg-red-500';
                                $badgeColor = $stepAnswered
                                    ? 'bg-green-100 dark:bg-green-950/60 text-green-700 dark:text-green-300 ring-green-200 dark:ring-green-800'
                                    : 'bg-red-100 dark:bg-red-950/60 text-red-700 dark:text-red-300 ring-red-200 dark:ring-red-800';

                                $ringLabel = null;
                                if ($stepAnswered && $answeredAt && ($step['started_at'] ?? null)) {
                                    $ringSecs = max(0, (int) \Carbon\Carbon::parse($answeredAt)->diffInSeconds(\Carbon\Carbon::parse($step['started_at'])));
                                    if ($ringSecs > 0) {
                                        $ringLabel = $this->formatSecondsShort($ringSecs);
                                    }
                                }

                                $actionNote = null;
                                if ($action === "terminé par la source") {
                                    $who = ($fromType === 'extension')
                                        ? ($pbxMap['extensions'][$fromDn] ?? $fromDn)
                                        : (($fromType === 'queue' && is_array($pbxMap['call_queues'][$fromDn] ?? null))
                                            ? ($pbxMap['call_queues'][$fromDn]['name'] ?? $fromDn)
                                            : $fromDn);
                                    $actionNote = __('expert-statistics::pbx.expert_statistics.cfa_ended_by', ['who' => $who]);
                                } elseif ($action === "terminé l'appel") {
                                    $who = ($toType === 'extension')
                                        ? ($pbxMap['extensions'][$toDn] ?? $toDn)
                                        : (($toType === 'queue' && is_array($pbxMap['call_queues'][$toDn] ?? null))
                                            ? ($pbxMap['call_queues'][$toDn]['name'] ?? $toDn)
                                            : $toDn);
                                    $actionNote = __('expert-statistics::pbx.expert_statistics.cfa_ended_by', ['who' => $who]);
                                } elseif ($action && $action !== '—' && $action !== "a demarré l'appel") {
                                    $actionNote = ucfirst($action);
                                }
                            @endphp

                            <div class="flex gap-3 {{ ! $isLastStep ? 'pb-3' : '' }}">

                                <div class="flex flex-col items-center shrink-0 w-5 pt-2">
                                    <div class="w-2.5 h-2.5 rounded-full shrink-0 ring-2 ring-white dark:ring-gray-900 {{ $nodeColor }}"></div>
                                    @if (! $isLastStep)
                                        <div class="mt-1 flex-1 w-px bg-gray-200 dark:bg-gray-700 min-h-5"></div>
                                    @endif
                                </div>

                                <div class="flex-1 min-w-0 bg-white dark:bg-gray-800/60 rounded-xl ring-1 ring-gray-100 dark:ring-gray-700/50 px-3.5 py-2.5">

                                    <div class="flex flex-wrap items-start justify-between gap-2">
                                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1 min-w-0 flex-1 text-sm">

                                            <div class="flex items-center gap-1 min-w-0">
                                                @if ($fromType === 'queue')
                                                    <x-heroicon-m-queue-list class="w-3.5 h-3.5 text-amber-500 shrink-0" />
                                                @elseif ($fromType === 'extension')
                                                    <x-heroicon-m-user class="w-3.5 h-3.5 text-blue-500 shrink-0" />
                                                @elseif (in_array($fromType, ['ivr', 'call_flow']))
                                                    <x-heroicon-m-adjustments-horizontal class="w-3.5 h-3.5 text-indigo-500 shrink-0" />
                                                @elseif ($fromType === 'voicemail')
                                                    <x-heroicon-m-envelope class="w-3.5 h-3.5 text-gray-400 shrink-0" />
                                                @else
                                                    <x-heroicon-m-phone class="w-3.5 h-3.5 text-gray-400 dark:text-gray-500 shrink-0" />
                                                @endif
                                                <span class="font-medium text-gray-800 dark:text-gray-200">{{ $fromDisplay }}</span>
                                                @if ($fromTypeLabel)
                                                    <span class="text-xs text-gray-400 dark:text-gray-500">({{ $fromTypeLabel }})</span>
                                                @endif
                                            </div>

                                            <x-heroicon-m-arrow-right class="w-3 h-3 text-gray-300 dark:text-gray-600 shrink-0" />

                                            <div class="flex items-center gap-1 min-w-0">
                                                @if ($toType === 'queue')
                                                    <x-heroicon-m-queue-list class="w-3.5 h-3.5 text-amber-500 shrink-0" />
                                                @elseif ($toType === 'extension')
                                                    <x-heroicon-m-user class="w-3.5 h-3.5 text-blue-500 shrink-0" />
                                                @elseif (in_array($toType, ['ivr', 'call_flow']))
                                                    <x-heroicon-m-adjustments-horizontal class="w-3.5 h-3.5 text-indigo-500 shrink-0" />
                                                @elseif ($toType === 'voicemail')
                                                    <x-heroicon-m-envelope class="w-3.5 h-3.5 text-gray-400 shrink-0" />
                                                @else
                                                    <x-heroicon-m-phone class="w-3.5 h-3.5 text-gray-400 dark:text-gray-500 shrink-0" />
                                                @endif
                                                <span class="font-medium text-gray-800 dark:text-gray-200">{{ $toDisplay }}</span>
                                                @if ($toTypeLabel)
                                                    <span class="text-xs text-gray-400 dark:text-gray-500">({{ $toTypeLabel }})</span>
                                                @endif
                                            </div>
                                        </div>

                                        <span class="inline-flex items-center text-xs font-medium px-2 py-0.5 rounded-full ring-1 shrink-0 {{ $badgeColor }}">
                                            {{ __($segmentLabelKey) }}
                                        </span>
                                    </div>

                                    <div class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-0.5 text-xs text-gray-400 dark:text-gray-500">
                                        <span class="tabular-nums">
                                            {{ \Carbon\Carbon::parse($step['started_at'] ?? 'now')->format('H:i:s') }}
                                            @if (! empty($step['ended_at']))
                                                → {{ \Carbon\Carbon::parse($step['ended_at'])->format('H:i:s') }}
                                            @endif
                                        </span>
                                        <span class="inline-flex items-center gap-1 tabular-nums">
                                            <x-heroicon-m-clock class="w-3 h-3" />
                                            {{ $stepSecs > 0 ? $this->formatSecondsShort($stepSecs) : $this->formatDuration($step['started_at'] ?? 'now', $step['ended_at'] ?? 'now') }}
                                        </span>
                                        @if ($ringLabel)
                                            <span class="inline-flex items-center gap-1 text-amber-600 dark:text-amber-400 tabular-nums">
                                                <x-heroicon-m-bell class="w-3 h-3 shrink-0" />
                                                {{ __('expert-statistics::pbx.expert_statistics.cfa_ring_wait', ['duration' => $ringLabel]) }}
                                            </span>
                                        @endif
                                        @if ($answeredAt && $stepAnswered)
                                            <span class="tabular-nums text-green-600 dark:text-green-400">
                                                ✓ {{ \Carbon\Carbon::parse($answeredAt)->format('H:i:s') }}
                                            </span>
                                        @endif
                                    </div>

                                    @if ($actionNote)
                                        <div class="mt-1.5 text-xs text-gray-500 dark:text-gray-400 italic">
                                            {{ $actionNote }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        @if ($pagination['last_page'] > 1)
        <div class="flex items-center justify-between pt-2">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('expert-statistics::pbx.expert_statistics.cfa_page_info', ['current' => $pagination['current_page'], 'last' => $pagination['last_page']]) }}
            </p>

            <div class="flex items-center gap-1">
                <button
                    wire:click="changePage({{ max(1, $pagination['current_page'] - 1) }})"
                    @disabled($pagination['current_page'] <= 1)
                    type="button"
                    class="px-3 py-1.5 rounded-lg text-sm font-medium transition
                        {{ $pagination['current_page'] <= 1
                            ? 'text-gray-300 dark:text-gray-600 cursor-not-allowed'
                            : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}"
                >
                    ‹
                </button>

                @php
                    $window = 2;
                    $current = $pagination['current_page'];
                    $last = $pagination['last_page'];
                    $pages = collect(range(max(1, $current - $window), min($last, $current + $window)));
                @endphp

                @if ($pages->first() > 1)
                    <button wire:click="changePage(1)" type="button" class="px-3 py-1.5 rounded-lg text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition">1</button>
                    @if ($pages->first() > 2) <span class="px-1 text-gray-400">…</span> @endif
                @endif

                @foreach ($pages as $p)
                    <button
                        wire:click="changePage({{ $p }})"
                        type="button"
                        class="px-3 py-1.5 rounded-lg text-sm font-medium transition
                            {{ $p === $current
                                ? 'bg-primary-600 text-white'
                                : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}"
                    >
                        {{ $p }}
                    </button>
                @endforeach

                @if ($pages->last() < $last)
                    @if ($pages->last() < $last - 1) <span class="px-1 text-gray-400">…</span> @endif
                    <button wire:click="changePage({{ $last }})" type="button" class="px-3 py-1.5 rounded-lg text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition">{{ $last }}</button>
                @endif

                <button
                    wire:click="changePage({{ min($last, $pagination['current_page'] + 1) }})"
                    @disabled($pagination['current_page'] >= $last)
                    type="button"
                    class="px-3 py-1.5 rounded-lg text-sm font-medium transition
                        {{ $pagination['current_page'] >= $last
                            ? 'text-gray-300 dark:text-gray-600 cursor-not-allowed'
                            : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}"
                >
                    ›
                </button>
            </div>
        </div>
        @endif

    @endif
    </div>{{-- end results relative wrapper --}}

</div>

</x-pages.index>
