@php
    $hostname = $this->activeHostName();
    $rows = $reportRows;
    $current = $pagination['current_page'];
    $last = $pagination['last_page'];
    $typeLabel = fn ($type, $rtype): string => match (true) {
        $rtype === 'cdrReport' => __('expert-statistics::pbx.reports.typeAll'),
        (string) $type === '0' => __('expert-statistics::pbx.reports.typeExtension'),
        (string) $type === '4' => __('expert-statistics::pbx.reports.typeQueue'),
        (string) $type === '99' => __('expert-statistics::pbx.reports.typeCallerNumber'),
        (string) $type === 'did' => __('expert-statistics::pbx.reports.typeNumber'),
        (string) $type === '*' => __('expert-statistics::pbx.reports.typeAll'),
        default => (string) $type,
    };
@endphp

<x-pages.index
    :title="__('expert-statistics::pbx.reports.title')"
    :subtitle="__('expert-statistics::pbx.reports.description')"
>

<div class="space-y-5">

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

    @if (! $hostname)
        <div class="rounded-xl border border-gray-200 bg-white px-6 py-10 text-center shadow-sm dark:border-white/10 dark:bg-gray-900/50">
            <x-heroicon-o-server class="mx-auto mb-3 h-10 w-10 text-gray-300 dark:text-gray-600" />
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('expert-statistics::pbx.reports.noHost') }}</p>
        </div>
    @else

        {{-- Search bar --}}
        <div class="relative max-w-sm">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <x-heroicon-m-magnifying-glass class="h-4 w-4 text-gray-400" />
            </div>
            <input type="text" wire:model.live.debounce.400ms="search" placeholder="{{ __('expert-statistics::pbx.reports.search') }}"
                class="block w-full rounded-lg border border-gray-300 bg-white py-2 pl-9 pr-3 text-sm text-gray-900 placeholder-gray-400 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder-gray-500" />
        </div>

        {{-- Table card --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900/50">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-white/5 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800/60">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('expert-statistics::pbx.reports.colName') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('expert-statistics::pbx.reports.colType') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('expert-statistics::pbx.reports.colElement') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('expert-statistics::pbx.reports.colGroup') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('expert-statistics::pbx.reports.colEmail') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('expert-statistics::pbx.reports.colStart') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('expert-statistics::pbx.reports.colFrequency') }}</th>
                            <th class="px-4 py-3 sr-only">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5 bg-white dark:bg-transparent">
                        @forelse ($rows as $row)
                            @php
                                $rowId = (int) $row['id'];
                                $dns = $row['dns'] ?? '';
                                $dnsList = ($dns && $dns !== '*') ? explode(',', $dns) : [];
                                $groupName = $row['resource_group']['name'] ?? null;
                                $emails = $row['email'] ?? '';
                                $emailList = $emails ? array_slice(explode(',', $emails), 0, 2) : [];
                                $emailExtra = $emails ? max(0, count(explode(',', $emails)) - 2) : 0;
                                $label = $typeLabel($row['element_type'] ?? '*', $row['report_type'] ?? '');
                                $startAt = isset($row['start_at']) ? \Carbon\Carbon::parse($row['start_at'])->format('d/m/Y') : '—';
                            @endphp
                            <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-white/5">
                                <td class="px-4 py-3.5 font-medium text-gray-900 dark:text-white">{{ $row['name'] ?? '—' }}</td>
                                <td class="px-4 py-3.5">
                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-white/10 dark:text-gray-300">{{ $label }}</span>
                                </td>
                                <td class="px-4 py-3.5">
                                    @if (empty($dnsList))
                                        <span class="text-gray-400">—</span>
                                    @else
                                        <div class="flex flex-wrap gap-1">
                                            @foreach (array_slice($dnsList, 0, 3) as $dn)
                                                <span class="inline-flex px-1.5 py-0.5 rounded bg-gray-100 text-xs font-mono text-gray-700 dark:bg-white/10 dark:text-gray-300">{{ trim($dn) }}</span>
                                            @endforeach
                                            @if (count($dnsList) > 3)
                                                <span class="text-xs text-gray-400 italic">+{{ count($dnsList) - 3 }}</span>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5">
                                    @if ($groupName)
                                        <span class="inline-flex items-center gap-1 text-xs font-medium text-primary-700 dark:text-primary-400">
                                            <x-heroicon-s-squares-2x2 class="h-3 w-3 shrink-0" />{{ $groupName }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="space-y-0.5">
                                        @foreach ($emailList as $em)
                                            <p class="truncate max-w-44 text-xs text-gray-600 dark:text-gray-400" title="{{ trim($em) }}">{{ trim($em) }}</p>
                                        @endforeach
                                        @if ($emailExtra > 0)
                                            <p class="text-xs text-gray-400 italic">+{{ $emailExtra }} more</p>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3.5 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $startAt }}</td>
                                <td class="px-4 py-3.5 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $row['repeat_pattern'] ?? '—' }}</td>
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center gap-1">
                                        <button type="button" wire:click="openEditReport({{ $rowId }})"
                                            wire:loading.attr="disabled"
                                            wire:loading.class="opacity-60 cursor-wait"
                                            wire:target="openEditReport({{ $rowId }})"
                                            title="{{ __('expert-statistics::pbx.reports.editTitle') }}"
                                            class="inline-flex items-center gap-1 rounded-md border border-gray-200 bg-white px-2 py-1 text-gray-600 shadow-sm hover:border-primary-300 hover:bg-primary-50 hover:text-primary-600 transition-colors dark:border-white/10 dark:bg-white/5 dark:text-gray-400 dark:hover:bg-primary-900/30 dark:hover:text-primary-400">
                                            <svg wire:loading wire:target="openEditReport({{ $rowId }})" class="animate-spin w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                            </svg>
                                            <x-heroicon-m-pencil-square wire:loading.remove wire:target="openEditReport({{ $rowId }})" class="w-4 h-4" />
                                        </button>
                                        <button type="button"
                                            wire:click="deleteReport({{ $rowId }})"
                                            wire:confirm="{{ __('expert-statistics::pbx.reports.confirmDelete') }}"
                                            title="{{ __('expert-statistics::pbx.reports.delete') }}"
                                            class="inline-flex items-center rounded-md border border-gray-200 bg-white px-2 py-1 text-gray-600 shadow-sm hover:border-red-300 hover:bg-red-50 hover:text-red-600 transition-colors dark:border-white/10 dark:bg-white/5 dark:text-gray-400 dark:hover:bg-red-900/30 dark:hover:text-red-400">
                                            <x-heroicon-m-trash class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-12 text-center">
                                    <x-heroicon-o-calendar-days class="mx-auto mb-3 h-10 w-10 text-gray-300 dark:text-gray-600" />
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('expert-statistics::pbx.reports.noReports') }}</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Pagination --}}
        @if ($pagination['total'] > 0)
            <div class="flex flex-wrap items-center justify-between gap-3 px-1">

                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('expert-statistics::pbx.reports.showing') }}
                    <span class="font-medium text-gray-700 dark:text-gray-300">{{ $pagination['from'] }}</span>
                    {{ __('expert-statistics::pbx.reports.to') }}
                    <span class="font-medium text-gray-700 dark:text-gray-300">{{ $pagination['to'] }}</span>
                    {{ __('expert-statistics::pbx.reports.of') }}
                    <span class="font-medium text-gray-700 dark:text-gray-300">{{ $pagination['total'] }}</span>
                    {{ __('expert-statistics::pbx.reports.results') }}
                </p>

                @if ($last > 1)
                    <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                        @foreach ($paginationLinks as $link)
                            @php
                                $linkPage = null;
                                if ($link['url']) {
                                    parse_str(parse_url($link['url'], PHP_URL_QUERY) ?? '', $qs);
                                    $linkPage = isset($qs['page']) ? (int) $qs['page'] : null;
                                }
                            @endphp
                            <button
                                @if ($link['url'] && $linkPage)
                                    wire:click="changePage({{ $linkPage }})"
                                @endif
                                @disabled(! $link['url'])
                                class="
                                    relative inline-flex items-center px-3 py-2 text-sm font-semibold ring-1 ring-inset ring-gray-300 dark:ring-gray-600 transition-colors
                                    {{ $loop->first ? 'rounded-l-md' : '' }}
                                    {{ $loop->last ? 'rounded-r-md' : '' }}
                                    {{ $link['active']
                                        ? 'z-10 bg-primary-600 text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600'
                                        : 'bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }}
                                    {{ ! $link['url'] ? 'cursor-not-allowed opacity-50' : 'cursor-pointer' }}
                                ">
                                @if ($loop->first)
                                    <x-heroicon-m-chevron-left class="h-4 w-4" />
                                @elseif ($loop->last)
                                    <x-heroicon-m-chevron-right class="h-4 w-4" />
                                @else
                                    {!! $link['label'] !!}
                                @endif
                            </button>
                        @endforeach
                    </nav>
                @endif

            </div>
        @endif

    @endif

    {{--
        Edit slide-over panel. Rendered inside the Livewire component via @if so
        wire:model/wire:click/wire:keydown all work normally. z-[9999] clears the
        host app's own topbar/sidebar chrome.
    --}}
    @if ($showEditPanel)
        <div class="fixed inset-0 z-[9999] overflow-hidden" role="dialog" aria-modal="true">

            <div class="absolute inset-0 bg-gray-900/60 transition-opacity" wire:click="cancelEditReport"></div>

            <div class="absolute inset-y-0 right-0 flex w-full max-w-md flex-col bg-white shadow-2xl dark:bg-gray-900">
                <div class="flex items-center justify-between border-b border-gray-200 dark:border-white/10 px-6 py-5 shrink-0">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                        {{ __('expert-statistics::pbx.reports.editTitle') }}
                    </h2>
                    <button wire:click="cancelEditReport"
                        class="rounded-md p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-white/10 dark:hover:text-gray-300 transition-colors">
                        <x-heroicon-m-x-mark class="w-5 h-5" />
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-6">

                    {{-- Report name --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            {{ __('expert-statistics::pbx.reports.editName') }}
                        </label>
                        <input type="text" wire:model="editForm.name"
                            class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm placeholder-gray-400 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white" />
                    </div>

                    {{-- Email recipients --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            {{ __('expert-statistics::pbx.reports.editRecipients') }}
                        </label>

                        <div class="flex gap-2">
                            <input type="email" wire:model="editForm.newEmail" wire:keydown.enter.prevent="addEmailToReport"
                                placeholder="{{ __('expert-statistics::pbx.reports.editAddPlaceholder') }}"
                                class="block flex-1 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm placeholder-gray-400 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder-gray-500" />
                            <button wire:click="addEmailToReport" type="button"
                                class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 transition-colors">
                                <x-heroicon-m-plus class="w-4 h-4" />
                            </button>
                        </div>

                        @if (count($editForm['emails']) > 0)
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                @foreach ($editForm['emails'] as $editEmail)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-primary-50 px-2.5 py-1 text-xs font-medium text-primary-700 ring-1 ring-inset ring-primary-600/20 dark:bg-primary-900/30 dark:text-primary-300 dark:ring-primary-500/30">
                                        {{ $editEmail }}
                                        {{-- Js::from() safely JSON-encodes the email before embedding it in wire:click, handling apostrophes/special chars. --}}
                                        <button wire:click="removeEmailFromReport({{ \Illuminate\Support\Js::from($editEmail) }})"
                                            type="button"
                                            class="ml-0.5 inline-flex h-3.5 w-3.5 items-center justify-center rounded-full text-primary-600 hover:bg-primary-100 hover:text-primary-900 dark:text-primary-400 dark:hover:bg-primary-800 transition-colors">
                                            <x-heroicon-s-x-mark class="h-2.5 w-2.5" />
                                        </button>
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- Resource group + elements (hidden for cdrReport) --}}
                    @if (($editForm['report_type'] ?? '') !== 'cdrReport')

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                {{ __('expert-statistics::pbx.reports.editResourceGroup') }}
                            </label>
                            {{-- wire:model.live triggers updatedEditFormResourceGroupId() server-side, which populates editForm.dns from the group's resources. --}}
                            <select wire:model.live="editForm.resource_group_id"
                                class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                                <option value="">{{ __('expert-statistics::pbx.reports.editResourceGroupNone') }}</option>
                                @foreach ($editResourceGroups as $rg)
                                    <option value="{{ $rg['id'] }}">{{ $rg['name'] }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1.5 flex items-start gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                                <x-heroicon-s-light-bulb class="mt-0.5 w-3.5 h-3.5 shrink-0 text-amber-500" />
                                {{ __('expert-statistics::pbx.reports.editResourceGroupHint') }}
                            </p>
                        </div>

                        @php
                            $elementType = $editForm['element_type'] ?? null;
                            $useCheckboxEdit = in_array((string) $elementType, ['0', '4']);
                            $editElements = match ((string) $elementType) {
                                '4' => $pbxMap['call_queues'] ?? [],
                                '0' => $pbxMap['extensions'] ?? [],
                                default => [],
                            };
                        @endphp

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                {{ __('expert-statistics::pbx.reports.editElements') }}
                            </label>

                            @if ($useCheckboxEdit && count($editElements) > 0)
                                <div class="max-h-52 overflow-y-auto rounded-lg border border-gray-200 bg-white p-2 space-y-0.5 dark:border-gray-600 dark:bg-gray-800">
                                    @foreach ($editElements as $elKey => $elValue)
                                        @php $elLabel = is_array($elValue) ? ($elValue['name'] ?? $elKey) : (string) $elValue; @endphp
                                        <label class="flex items-center gap-2.5 rounded-md px-2 py-1.5 cursor-pointer text-sm hover:bg-gray-50 dark:hover:bg-gray-700/50 select-none">
                                            <input type="checkbox"
                                                wire:click="toggleDnsElement({{ \Illuminate\Support\Js::from((string) $elKey) }})"
                                                @checked(in_array((string) $elKey, $editForm['dns'], true))
                                                class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-gray-600 cursor-pointer" />
                                            <span class="w-10 shrink-0 font-mono text-xs text-gray-400 dark:text-gray-500">{{ $elKey }}</span>
                                            <span class="text-gray-700 dark:text-gray-300">{{ $elLabel }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            @elseif (! $useCheckboxEdit)
                                {{-- DID / caller: show current dns as read-only tags --}}
                                <div class="flex min-h-9 flex-wrap gap-1.5 rounded-lg border border-gray-200 bg-white p-2 dark:border-gray-600 dark:bg-gray-800">
                                    @forelse ($editForm['dns'] as $dn)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-mono text-gray-700 dark:bg-white/10 dark:text-gray-300">
                                            {{ $dn }}
                                            <button wire:click="toggleDnsElement({{ \Illuminate\Support\Js::from($dn) }})" type="button"
                                                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                                <x-heroicon-s-x-mark class="h-2.5 w-2.5" />
                                            </button>
                                        </span>
                                    @empty
                                        <span class="text-xs text-gray-400">—</span>
                                    @endforelse
                                </div>
                            @else
                                <p class="text-sm text-gray-400">{{ __('expert-statistics::pbx.config.groups.noElements') }}</p>
                            @endif
                        </div>

                    @endif

                </div>

                <div class="shrink-0 border-t border-gray-200 dark:border-white/10 px-6 py-4 flex items-center justify-end gap-3">
                    <button wire:click="cancelEditReport" type="button"
                        class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 transition-colors">
                        {{ __('expert-statistics::pbx.reports.editCancel') }}
                    </button>
                    <button wire:click="saveEditReport" type="button"
                        class="inline-flex items-center rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors">
                        {{ __('expert-statistics::pbx.reports.editSave') }}
                    </button>
                </div>

            </div>
        </div>
    @endif

</div>

</x-pages.index>
