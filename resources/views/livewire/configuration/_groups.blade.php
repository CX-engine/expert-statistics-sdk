@php
    $groupSubTabs = [
        'queue' => __('expert-statistics::pbx.config.groups.subTab.queue'),
        'extension' => __('expert-statistics::pbx.config.groups.subTab.extension'),
        'did' => __('expert-statistics::pbx.config.groups.subTab.did'),
        'caller' => __('expert-statistics::pbx.config.groups.subTab.caller'),
    ];
    $typeMap = ['queue' => 4, 'extension' => 0, 'did' => 1, 'caller' => 99];
    $activeType = $typeMap[$groupsSubTab] ?? 4;
    $filteredGroups = array_values(array_filter($groups, fn ($g) => (int) ($g['type'] ?? -1) === $activeType));
    $queueElements = $pbxMap['call_queues'] ?? [];
    $extElements = $pbxMap['extensions'] ?? [];
@endphp

<div class="space-y-5">

    {{-- Type switcher row + action button --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-1 rounded-xl bg-gray-100 dark:bg-gray-800 p-1">
            @foreach ($groupSubTabs as $key => $subLabel)
                <button wire:click="switchGroupsSubTab('{{ $key }}')" type="button"
                    class="inline-flex items-center rounded-lg px-3 py-1.5 text-sm font-medium whitespace-nowrap transition-all
                        {{ $groupsSubTab === $key
                            ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm'
                            : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' }}">
                    {{ $subLabel }}
                </button>
            @endforeach
        </div>

        @if ($this->activeHostName() && ! $showGroupForm && $this->canModify())
            <button wire:click="openCreateGroupForm" type="button"
                class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-3.5 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors">
                <x-heroicon-m-plus class="h-4 w-4" />
                {{ __('expert-statistics::pbx.config.groups.newGroup') }}
            </button>
        @endif
    </div>

    @if (! $this->activeHostName())
        <div class="rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900/50 px-6 py-10 text-center shadow-sm">
            <x-heroicon-o-server class="mx-auto mb-3 h-10 w-10 text-gray-300 dark:text-gray-600" />
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('expert-statistics::pbx.config.groups.noHost') }}</p>
        </div>
    @else

        {{-- Create / Edit form --}}
        @if ($showGroupForm)
            @php
                $dids = $pbxMap['dids'] ?? [];
                $didElements = $dids ? array_combine($dids, $dids) : [];
                $availableElements = match ($groupsSubTab) {
                    'queue' => $queueElements,
                    'extension' => $extElements,
                    'did' => $didElements,
                    default => [],
                };
                $useCheckboxes = in_array($groupsSubTab, ['queue', 'extension', 'did']);
            @endphp

            <div class="rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900/50 shadow-sm">
                <div class="flex items-center justify-between border-b border-gray-100 dark:border-white/5 px-5 py-4">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                        {{ $editingGroupId ? __('expert-statistics::pbx.config.groups.editTitle') : __('expert-statistics::pbx.config.groups.createTitle') }}
                    </h3>
                    <button wire:click="cancelGroupForm" type="button"
                        class="rounded-md p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-white/10 dark:hover:text-gray-300 transition-colors">
                        <x-heroicon-m-x-mark class="h-4 w-4" />
                    </button>
                </div>

                <div class="space-y-4 p-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            {{ __('expert-statistics::pbx.config.groups.name') }}
                        </label>
                        <input type="text" wire:model="groupForm.name"
                            class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white shadow-sm placeholder-gray-400 dark:placeholder-gray-500 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            {{ __('expert-statistics::pbx.config.groups.selectMembers') }}
                        </label>

                        @if ($useCheckboxes)
                            @if (count($availableElements) > 0)
                                <div class="max-h-56 overflow-y-auto rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 p-2 space-y-0.5">
                                    @foreach ($availableElements as $elKey => $element)
                                        @php $elLabel = is_array($element) ? ($element['name'] ?? $elKey) : (string) $element; @endphp
                                        <label class="flex items-center gap-2.5 rounded-md px-2 py-1.5 cursor-pointer text-sm hover:bg-gray-50 dark:hover:bg-gray-700">
                                            <input type="checkbox" wire:model="groupForm.resources" value="{{ $elKey }}"
                                                class="h-4 w-4 rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500" />
                                            <span class="font-mono text-xs text-gray-400 dark:text-gray-500">{{ $elKey }}</span>
                                            @if ($elKey !== $elLabel)
                                                <span class="text-gray-700 dark:text-gray-300">{{ $elLabel }}</span>
                                            @endif
                                        </label>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('expert-statistics::pbx.config.groups.noElements') }}</p>
                            @endif
                        @else
                            {{-- Caller number search + chips --}}
                            <div class="relative">
                                <input type="text" wire:model.live.debounce.400ms="callerSearch"
                                    placeholder="{{ __('expert-statistics::pbx.expert_statistics.cfa_caller_search_placeholder') }}" autocomplete="off"
                                    class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white shadow-sm placeholder-gray-400 dark:placeholder-gray-500 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500" />
                                @if (count($callerSearchResults) > 0)
                                    <ul class="absolute z-20 mt-1 w-full rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 shadow-lg max-h-48 overflow-y-auto">
                                        @foreach ($callerSearchResults as $callerNumber)
                                            <li>
                                                <button type="button" wire:click="addCallerResource('{{ $callerNumber }}')"
                                                    class="w-full px-3 py-2 text-left text-sm text-gray-700 dark:text-gray-300 hover:bg-primary-50 dark:hover:bg-primary-950/30 hover:text-primary-700 dark:hover:text-primary-400 font-mono">{{ $callerNumber }}</button>
                                            </li>
                                        @endforeach
                                    </ul>
                                @elseif (\strlen($callerSearch) >= 2 && count($callerSearchResults) === 0)
                                    <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ __('expert-statistics::pbx.config.queue.noQueues') }}</p>
                                @else
                                    <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ __('expert-statistics::pbx.expert_statistics.cfa_caller_title') }} (min. 2 chars)</p>
                                @endif
                            </div>

                            @if (count($groupForm['resources']) > 0)
                                <div class="mt-2 flex flex-wrap gap-1.5">
                                    @foreach ($groupForm['resources'] as $callerResource)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-primary-100 dark:bg-primary-950/40 px-2.5 py-0.5 text-xs font-medium text-primary-800 dark:text-primary-300">
                                            <span class="font-mono">{{ $callerResource }}</span>
                                            <button type="button" wire:click="removeCallerResource('{{ $callerResource }}')"
                                                class="ml-0.5 rounded-full p-0.5 hover:bg-primary-200 dark:hover:bg-primary-800 transition-colors">
                                                <x-heroicon-m-x-mark class="h-3 w-3" />
                                            </button>
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">{{ __('expert-statistics::pbx.config.groups.noElements') }}</p>
                            @endif
                        @endif
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-gray-100 dark:border-white/5 px-5 py-4">
                    <button wire:click="cancelGroupForm" type="button"
                        class="inline-flex items-center rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        {{ __('expert-statistics::pbx.config.groups.cancel') }}
                    </button>
                    <button wire:click="saveGroup" type="button"
                        class="inline-flex items-center rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors">
                        {{ __('expert-statistics::pbx.config.save') }}
                    </button>
                </div>
            </div>
        @endif

        {{-- Groups table --}}
        @if (count($filteredGroups) > 0)
            <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900/50 shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-white/5 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800/60">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                {{ __('expert-statistics::pbx.config.groups.name') }}
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                {{ __('expert-statistics::pbx.config.groups.members') }}
                            </th>
                            <th class="px-4 py-3 sr-only">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5 bg-white dark:bg-transparent">
                        @foreach ($filteredGroups as $group)
                            @php
                                $resources = $group['resources'] ?? [];
                                if (is_string($resources)) {
                                    $resources = json_decode($resources, true) ?? [];
                                }
                            @endphp
                            <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-white/5">
                                <td class="px-4 py-3.5 font-medium text-gray-900 dark:text-white">
                                    {{ $group['name'] }}
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach (array_slice($resources, 0, 6) as $res)
                                            @php
                                                $resLabel = match (true) {
                                                    $activeType === 4 && isset($queueElements[$res]) => $queueElements[$res]['name'] ?? $res,
                                                    $activeType === 0 && isset($extElements[$res]) => $extElements[$res],
                                                    default => null,
                                                };
                                            @endphp
                                            <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 dark:bg-white/10 px-2 py-0.5 text-xs font-medium text-gray-700 dark:text-gray-300">
                                                <span class="font-mono">{{ $res }}</span>
                                                @if ($resLabel)
                                                    <span class="text-gray-400 dark:text-gray-500">{{ $resLabel }}</span>
                                                @endif
                                            </span>
                                        @endforeach
                                        @if (count($resources) > 6)
                                            <span class="text-xs text-gray-500 dark:text-gray-400 italic">+{{ count($resources) - 6 }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3.5">
                                    @if ($this->canModify())
                                        <div class="flex items-center justify-end gap-1">
                                            <button wire:click="openEditGroupForm({{ $group['id'] }})" type="button"
                                                class="inline-flex items-center rounded-md border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 px-2 py-1 text-gray-600 dark:text-gray-400 shadow-sm hover:border-primary-300 hover:bg-primary-50 hover:text-primary-600 dark:hover:bg-primary-950/30 dark:hover:text-primary-400 transition-colors">
                                                <x-heroicon-m-pencil-square class="w-4 h-4" />
                                            </button>
                                            <button wire:click="deleteGroup({{ $group['id'] }})" wire:confirm="{{ __('expert-statistics::pbx.config.groups.confirmDelete') }}" type="button"
                                                class="inline-flex items-center rounded-md border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 px-2 py-1 text-gray-600 dark:text-gray-400 shadow-sm hover:border-red-300 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/30 dark:hover:text-red-400 transition-colors">
                                                <x-heroicon-m-trash class="w-4 h-4" />
                                            </button>
                                        </div>
                                    @else
                                        <span class="text-xs text-gray-400 dark:text-gray-500">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @elseif (! $showGroupForm)
            <div class="rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900/50 px-6 py-12 text-center shadow-sm">
                <x-heroicon-o-squares-2x2 class="mx-auto mb-3 h-10 w-10 text-gray-300 dark:text-gray-600" />
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('expert-statistics::pbx.config.groups.noGroups') }}</p>
            </div>
        @endif
    @endif
</div>
