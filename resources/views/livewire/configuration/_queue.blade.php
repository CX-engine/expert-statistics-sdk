<div class="space-y-6">

    {{-- Queue selector + preanswer time form --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-x-8 gap-y-6">
        <div>
            <h2 class="text-base font-semibold leading-7 text-gray-900 dark:text-white">{{ __('expert-statistics::pbx.config.queue.title') }}</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('expert-statistics::pbx.config.queue.description') }}</p>
        </div>
        <div class="md:col-span-2 rounded-2xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-5 space-y-5">
            <div>
                <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">
                    {{ __('expert-statistics::pbx.config.queue.duration') }} ({{ __('expert-statistics::pbx.config.queue.seconds') }})
                </label>
                <input type="number" min="0" max="500" wire:model="queueForm.preanswer_seconds"
                    class="block w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 dark:text-white shadow-sm text-sm focus:ring-primary-500 focus:border-primary-500" />
            </div>

            @if (count($availableQueues) > 0)
                <div>
                    <label class="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">{{ __('expert-statistics::pbx.config.queue.selectQueues') }}</label>
                    <div class="space-y-1 max-h-52 overflow-y-auto rounded-lg border border-gray-200 dark:border-gray-700 p-2">
                        @foreach ($availableQueues as $queue)
                            <label class="flex items-center gap-2 px-2 py-1 rounded hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer text-sm">
                                <input type="checkbox" wire:model="selectedQueues" value="{{ $queue['value'] }}"
                                    class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500" />
                                <span class="text-gray-700 dark:text-gray-300">{{ $queue['label'] }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('expert-statistics::pbx.config.queue.noQueues') }}</p>
            @endif

            <div class="flex justify-end">
                <button wire:click="saveQueueConfig" type="button"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 hover:bg-primary-700 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors">
                    {{ __('expert-statistics::pbx.config.save') }}
                </button>
            </div>
        </div>
    </div>

    {{-- Preanswer times list --}}
    <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
        {{-- Sub-tabs --}}
        <div class="flex gap-1 border-b border-gray-200 dark:border-gray-700 mb-4">
            <button wire:click="$set('queueSubTab', 'current')" type="button"
                class="px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors
                    {{ $queueSubTab === 'current' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' }}">
                {{ __('expert-statistics::pbx.config.queue.current') }}
            </button>
            <button wire:click="$set('queueSubTab', 'history')" type="button"
                class="px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors
                    {{ $queueSubTab === 'history' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' }}">
                {{ __('expert-statistics::pbx.config.queue.history') }}
            </button>
        </div>

        {{-- Bulk delete --}}
        @if (count($selectedItems) > 0)
            <div class="flex justify-end mb-2">
                <button wire:click="bulkDeletePreanswerTimes" wire:confirm="{{ __('expert-statistics::pbx.config.queue.confirmBulkDelete') }}" type="button"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-red-600 hover:bg-red-700 px-3 py-1.5 text-xs font-medium text-white shadow-sm transition-colors">
                    {{ __('expert-statistics::pbx.config.queue.deleteSelected') }} ({{ count($selectedItems) }})
                </button>
            </div>
        @endif

        {{-- Search --}}
        <div class="flex justify-end mb-2">
            <input type="text" wire:model.live.debounce.400ms="queueSearch"
                placeholder="{{ __('expert-statistics::pbx.config.search') }}"
                class="w-full sm:w-1/3 rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 dark:text-white text-xs shadow-sm focus:ring-primary-500 focus:border-primary-500" />
        </div>

        {{-- Current table --}}
        @if ($queueSubTab === 'current')
            @php
                $currentIds = array_column($preanswerCurrent, 'id');
                $allCurrentSelected = count($currentIds) > 0 && count(array_diff($currentIds, $selectedItems)) === 0;
            @endphp
            <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
                <table class="min-w-full text-sm text-left">
                    <thead class="bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-3 w-10">
                                <input type="checkbox" wire:click="toggleSelectAll" @checked($allCurrentSelected)
                                    class="rounded border-gray-300 dark:border-gray-600 text-primary-600" />
                            </th>
                            <th class="px-4 py-3">{{ __('expert-statistics::pbx.config.queue.queueLabel') }}</th>
                            <th class="px-4 py-3">{{ __('expert-statistics::pbx.config.queue.preanswerTime') }}</th>
                            <th class="px-4 py-3">{{ __('expert-statistics::pbx.config.queue.createdBy') }}</th>
                            <th class="px-4 py-3 sr-only">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($preanswerCurrent as $record)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="px-4 py-3">
                                    <input type="checkbox" wire:model="selectedItems" value="{{ $record['id'] }}"
                                        class="rounded border-gray-300 dark:border-gray-600 text-primary-600" />
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-gray-700 dark:text-gray-300">{{ $record['label'] ?: $record['queue'] }}</td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $record['preanswer_seconds'] }}s</td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $record['created_by'] ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <button wire:click="deletePreanswerTime({{ $record['id'] }})" wire:confirm="{{ __('expert-statistics::pbx.config.queue.confirmDelete') }}" type="button"
                                        class="rounded-md px-2 py-1 text-sm font-semibold ring-1 ring-inset ring-gray-300 dark:ring-gray-600 bg-white dark:bg-gray-800 hover:bg-red-50 dark:hover:bg-red-950/30 hover:text-red-500 hover:ring-red-400 transition-colors">
                                        <x-heroicon-m-trash class="w-4 h-4" />
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                    {{ __('expert-statistics::pbx.config.queue.noConfigs') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        {{-- History table --}}
        @if ($queueSubTab === 'history')
            @php
                $historyIds = array_column($preanswerHistory['data'] ?? [], 'id');
                $allHistorySelected = count($historyIds) > 0 && count(array_diff($historyIds, $selectedItems)) === 0;
            @endphp
            <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
                <table class="min-w-full text-sm text-left">
                    <thead class="bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-3 w-10">
                                <input type="checkbox" wire:click="toggleSelectAll" @checked($allHistorySelected)
                                    class="rounded border-gray-300 dark:border-gray-600 text-primary-600" />
                            </th>
                            <th class="px-4 py-3">{{ __('expert-statistics::pbx.config.queue.queueLabel') }}</th>
                            <th class="px-4 py-3">{{ __('expert-statistics::pbx.config.queue.preanswerTime') }}</th>
                            <th class="px-4 py-3">{{ __('expert-statistics::pbx.config.queue.createdAt') }}</th>
                            <th class="px-4 py-3">{{ __('expert-statistics::pbx.config.queue.createdBy') }}</th>
                            <th class="px-4 py-3 sr-only">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($preanswerHistory['data'] ?? [] as $record)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="px-4 py-3">
                                    <input type="checkbox" wire:model="selectedItems" value="{{ $record['id'] }}"
                                        class="rounded border-gray-300 dark:border-gray-600 text-primary-600" />
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-gray-700 dark:text-gray-300">{{ $record['label'] ?: $record['queue'] }}</td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $record['preanswer_seconds'] }}s</td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs">
                                    {{ \Carbon\Carbon::parse($record['created_at'])->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $record['created_by'] ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <button wire:click="deletePreanswerTime({{ $record['id'] }})" wire:confirm="{{ __('expert-statistics::pbx.config.queue.confirmDelete') }}" type="button"
                                        class="rounded-md px-2 py-1 text-sm font-semibold ring-1 ring-inset ring-gray-300 dark:ring-gray-600 bg-white dark:bg-gray-800 hover:bg-red-50 dark:hover:bg-red-950/30 hover:text-red-500 hover:ring-red-400 transition-colors">
                                        <x-heroicon-m-trash class="w-4 h-4" />
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                    {{ __('expert-statistics::pbx.config.queue.noHistory') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if (($preanswerHistory['last_page'] ?? 1) > 1)
                <div class="flex items-center justify-between mt-3">
                    <button wire:click="changePage({{ ($preanswerHistory['current_page'] ?? 1) - 1 }})"
                        @disabled(($preanswerHistory['current_page'] ?? 1) <= 1) type="button"
                        class="inline-flex items-center rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 transition-colors">
                        &larr; {{ __('expert-statistics::pbx.config.prev') }}
                    </button>
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $preanswerHistory['current_page'] ?? 1 }} / {{ $preanswerHistory['last_page'] ?? 1 }}
                    </span>
                    <button wire:click="changePage({{ ($preanswerHistory['current_page'] ?? 1) + 1 }})"
                        @disabled(($preanswerHistory['current_page'] ?? 1) >= ($preanswerHistory['last_page'] ?? 1)) type="button"
                        class="inline-flex items-center rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 transition-colors">
                        {{ __('expert-statistics::pbx.config.next') }} &rarr;
                    </button>
                </div>
            @endif
        @endif
    </div>
</div>
