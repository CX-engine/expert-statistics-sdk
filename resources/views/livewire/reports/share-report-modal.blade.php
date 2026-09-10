@php
    $isDashboardMode = $urlType === null;
    $isCallerReport = $urlType === 'caller';
    $hasCallerFilters = $isCallerReport && ! empty($callerFilters);
    $hasElements = ! $isDashboardMode && ! $isCallerReport && filled($elements);
@endphp

<div>
    @if ($isOpen)
        {{-- Backdrop --}}
        <div
            class="fixed inset-0 z-40 bg-black/60 backdrop-blur-sm transition-opacity"
            wire:click="close"
        ></div>

        {{-- Dialog --}}
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="w-full max-w-lg bg-white dark:bg-gray-900 rounded-2xl shadow-2xl ring-1 ring-gray-950/10 dark:ring-white/10 overflow-hidden">

                {{-- Header --}}
                <div class="flex items-center gap-3 px-6 pt-5 pb-4 border-b border-gray-100 dark:border-gray-800">
                    <div class="flex items-center justify-center h-9 w-9 rounded-xl shrink-0
                        {{ $isSchedule ? 'bg-purple-50 dark:bg-purple-950/50' : 'bg-primary-50 dark:bg-primary-950/50' }}">
                        @if ($isSchedule)
                            <x-heroicon-o-calendar-days class="h-5 w-5 text-purple-600 dark:text-purple-400" />
                        @else
                            <x-heroicon-o-share class="h-5 w-5 text-primary-600 dark:text-primary-400" />
                        @endif
                    </div>
                    <h3 class="flex-1 text-base font-semibold text-gray-900 dark:text-white">
                        {{ $isSchedule ? __('expert-statistics::pbx.dashboards.scheduleReport') : __('expert-statistics::pbx.dashboards.sendReport') }}
                    </h3>
                    <button wire:click="close" type="button"
                        class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 transition shrink-0">
                        <x-heroicon-o-x-mark class="h-4 w-4" />
                    </button>
                </div>

                {{-- Body --}}
                <div class="px-6 py-5 space-y-5 max-h-[70vh] overflow-y-auto">

                    {{-- Feedback banner --}}
                    @if ($statusMessage)
                        <div @class([
                            'rounded-xl border px-4 py-3 text-sm',
                            'bg-emerald-50 dark:bg-emerald-950/30 border-emerald-200 dark:border-emerald-900 text-emerald-700 dark:text-emerald-400' => $statusType === 'success',
                            'bg-red-50 dark:bg-red-950/30 border-red-200 dark:border-red-900 text-red-700 dark:text-red-400' => $statusType === 'error',
                            'bg-amber-50 dark:bg-amber-950/30 border-amber-200 dark:border-amber-900 text-amber-700 dark:text-amber-400' => $statusType === 'warning',
                        ])>
                            {{ $statusMessage }}
                        </div>
                    @endif

                    {{-- Report name --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1.5">
                            {{ __('expert-statistics::pbx.dashboards.reportNicknamePlaceholder') }}
                        </label>
                        <input
                            type="text"
                            wire:model="nickname"
                            placeholder="{{ __('expert-statistics::pbx.dashboards.reportNicknamePlaceholder') }}"
                            class="w-full text-sm rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 placeholder-gray-400 dark:placeholder-gray-500 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition"
                        />
                    </div>

                    @if ($isSchedule)
                        {{-- Schedule options --}}
                        <div class="space-y-3">
                            <div class="flex flex-wrap gap-4 items-end">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1.5">
                                        {{ __('expert-statistics::pbx.dashboards.reportType') }}
                                    </label>
                                    <div class="inline-flex rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 divide-x divide-gray-200 dark:divide-gray-700 overflow-hidden">
                                        @foreach (['day' => __('expert-statistics::pbx.dashboards.daily'), 'week' => __('expert-statistics::pbx.dashboards.weekly'), 'month' => __('expert-statistics::pbx.dashboards.monthly')] as $val => $label)
                                            <button type="button" wire:click="$set('cron', '{{ $val }}')"
                                                class="px-3 py-1.5 text-sm font-medium transition
                                                    {{ $cron === $val
                                                        ? 'bg-primary-600 text-white'
                                                        : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                                                {{ $label }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1.5">
                                        {{ __('expert-statistics::pbx.dashboards.startingFrom') }}
                                    </label>
                                    <input type="date" wire:model="startAt"
                                        class="text-sm rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition" />
                                </div>
                            </div>
                            <p class="text-xs text-gray-400 dark:text-gray-500">
                                {{ $isDashboardMode ? __('expert-statistics::pbx.dashboards.report_schedule_info') : __('expert-statistics::pbx.expert_statistics.report_schedule_info') }}
                            </p>
                        </div>
                    @else
                        {{-- Period summary for a one-time send --}}
                        @if ($startDate && $endDate)
                            <div>
                                <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1.5">
                                    {{ $isDashboardMode ? __('expert-statistics::pbx.dashboards.report_period') : __('expert-statistics::pbx.expert_statistics.report_period') }}
                                </p>
                                <span class="inline-flex items-center gap-1.5 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-sm px-2.5 py-1 ring-1 ring-gray-200 dark:ring-gray-700">
                                    <x-heroicon-m-calendar class="w-3.5 h-3.5 text-gray-400 shrink-0" />
                                    {{ $startDate }} – {{ $endDate }}
                                    @if ($startTime || $endTime)
                                        · {{ $startTime }} – {{ $endTime }}
                                    @endif
                                </span>
                            </div>
                        @endif
                    @endif

                    {{-- Selected elements (report-page mode only) --}}
                    @if ($hasCallerFilters || $hasElements)
                        <div>
                            <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">
                                {{ __('expert-statistics::pbx.expert_statistics.report_elements') }}
                            </p>

                            @if ($hasCallerFilters)
                                <div class="space-y-2">
                                    {{-- Group selection --}}
                                    <div class="flex flex-wrap gap-1.5">
                                        @if (! empty($callerFilters['all_groups']))
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-primary-50 dark:bg-primary-950/40 text-primary-700 dark:text-primary-300 text-xs font-medium px-2.5 py-1 ring-1 ring-primary-200 dark:ring-primary-800">
                                                <x-heroicon-m-squares-2x2 class="w-3 h-3 shrink-0" />
                                                {{ __('expert-statistics::pbx.expert_statistics.selector_all_groups') }}
                                            </span>
                                        @elseif (! empty($callerFilters['groupless']))
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 text-xs font-medium px-2.5 py-1 ring-1 ring-amber-200 dark:ring-amber-800">
                                                <x-heroicon-m-tag class="w-3 h-3 shrink-0" />
                                                {{ __('expert-statistics::pbx.expert_statistics.caller_numbers_tab_groupless') }}
                                            </span>
                                        @elseif (! empty($callerFilters['group_names']))
                                            @php
                                                $groupNames = (array) $callerFilters['group_names'];
                                                $visibleGroups = array_slice($groupNames, 0, 10);
                                                $groupOverflow = count($groupNames) - count($visibleGroups);
                                            @endphp
                                            @foreach ($visibleGroups as $groupName)
                                                <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-xs font-medium px-2.5 py-1 ring-1 ring-gray-200 dark:ring-gray-700">
                                                    {{ $groupName }}
                                                </span>
                                            @endforeach
                                            @if ($groupOverflow > 0)
                                                <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-800 text-gray-400 dark:text-gray-500 text-xs px-2.5 py-1 ring-1 ring-gray-200 dark:ring-gray-700">
                                                    +{{ $groupOverflow }} {{ __('expert-statistics::pbx.expert_statistics.selector_show_more') }}
                                                </span>
                                            @endif
                                        @endif
                                    </div>

                                    {{-- Active queue filter --}}
                                    @if (! empty($callerFilters['queues']))
                                        @php $activeQueues = (array) $callerFilters['queues']; @endphp
                                        <div class="flex flex-wrap gap-1.5 items-center">
                                            <span class="text-xs text-gray-400 dark:text-gray-500">
                                                {{ __('expert-statistics::pbx.expert_statistics.caller_numbers_queue_active') }}
                                            </span>
                                            @foreach ($activeQueues as $queueDn)
                                                <span class="inline-flex items-center rounded-full bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 text-xs font-medium px-2.5 py-1 ring-1 ring-blue-200 dark:ring-blue-800 font-mono">
                                                    {{ $queueDn }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @else
                                {{-- DN chip list for queue / DID / extension reports --}}
                                @php
                                    $dnList = array_values(array_filter(array_map('trim', explode(',', $elements))));
                                    $visibleDns = array_slice($dnList, 0, 10);
                                    $dnOverflow = count($dnList) - count($visibleDns);
                                @endphp
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach ($visibleDns as $dn)
                                        <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-xs font-medium px-2.5 py-1 ring-1 ring-gray-200 dark:ring-gray-700 font-mono">
                                            {{ $dn }}
                                        </span>
                                    @endforeach
                                    @if ($dnOverflow > 0)
                                        <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-800 text-gray-400 dark:text-gray-500 text-xs px-2.5 py-1 ring-1 ring-gray-200 dark:ring-gray-700">
                                            +{{ $dnOverflow }} {{ __('expert-statistics::pbx.expert_statistics.selector_show_more') }}
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- Resource group (report-page mode only, not for caller reports) --}}
                    @if (! $isDashboardMode && ! $isCallerReport && count($resourceGroups) > 0)
                        <div>
                            <label class="block text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1.5">
                                {{ __('expert-statistics::pbx.expert_statistics.report_resource_group') }}
                            </label>
                            <select wire:model="pbx3cx_host_resource_group_id"
                                class="w-full text-sm rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                                <option value="">{{ __('expert-statistics::pbx.expert_statistics.report_resource_group_none') }}</option>
                                @foreach ($resourceGroups as $group)
                                    <option value="{{ $group['id'] }}">{{ $group['name'] }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                                {{ __('expert-statistics::pbx.expert_statistics.report_resource_group_hint') }}
                            </p>
                        </div>
                    @endif

                    {{-- Recipients --}}
                    <div class="space-y-2">
                        <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                            {{ __('expert-statistics::pbx.dashboards.reportRecipientsLabel') }}
                        </p>
                        <form wire:submit.prevent="addEmail" class="flex gap-2">
                            <div class="flex-1">
                                <input
                                    type="email"
                                    wire:model="email"
                                    placeholder="you@example.com"
                                    class="w-full text-sm rounded-lg border bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 placeholder-gray-400 dark:placeholder-gray-500 px-3 py-2 focus:outline-none focus:ring-2 transition
                                        {{ $invalidEmail
                                            ? 'border-red-400 dark:border-red-500 focus:ring-red-500 focus:border-red-500'
                                            : 'border-gray-200 dark:border-gray-700 focus:ring-primary-500 focus:border-primary-500' }}"
                                />
                                @if ($invalidEmail)
                                    <p class="mt-1 text-xs text-red-500 dark:text-red-400">
                                        {{ $emailAlreadyExists
                                            ? __('expert-statistics::pbx.expert_statistics.report_email_duplicate')
                                            : __('expert-statistics::pbx.expert_statistics.report_email_invalid') }}
                                    </p>
                                @endif
                            </div>
                            <button type="submit"
                                class="px-3 py-2 text-sm font-medium rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 whitespace-nowrap self-start transition">
                                {{ __('expert-statistics::pbx.dashboards.addRecipient') }}
                            </button>
                        </form>

                        @if (count($emails) > 0)
                            <div class="flex flex-wrap gap-1.5 pt-1">
                                @foreach ($emails as $addr)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-xs font-medium pl-2.5 pr-1 py-1 ring-1 ring-gray-200 dark:ring-gray-700">
                                        {{ $addr }}
                                        <button wire:click="removeEmail('{{ $addr }}')" type="button"
                                            class="rounded-full p-0.5 text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600 hover:text-gray-600 dark:hover:text-gray-200 transition">
                                            <x-heroicon-m-x-mark class="h-3 w-3" />
                                        </button>
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>

                </div>

                {{-- Footer --}}
                <div class="flex items-center justify-end gap-2 px-6 pb-5">
                    <button wire:click="close" type="button"
                        class="px-4 py-2 text-sm font-medium rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        {{ __('expert-statistics::pbx.dashboards.cancel') }}
                    </button>
                    <button wire:click="submit" type="button" @disabled($isLoading)
                        class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium rounded-xl transition disabled:opacity-60 disabled:cursor-wait
                            {{ $isSchedule ? 'bg-purple-600 hover:bg-purple-700' : 'bg-primary-600 hover:bg-primary-700' }} text-white">
                        <svg wire:loading wire:target="submit" class="animate-spin w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        {{ $isSchedule ? __('expert-statistics::pbx.dashboards.schedule') : __('expert-statistics::pbx.dashboards.send') }}
                    </button>
                </div>

            </div>
        </div>
    @endif
</div>
