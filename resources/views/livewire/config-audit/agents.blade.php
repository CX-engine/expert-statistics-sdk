<div class="space-y-4">
    <input type="search" wire:model.live.debounce.400ms="agentSearch" placeholder="{{ $t('search_agents') }}"
           class="w-full sm:w-80 rounded-lg border-gray-200 dark:border-gray-700 dark:bg-gray-800 text-sm py-1.5" />

    <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-lg shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-900/40 text-xs text-gray-500 dark:text-gray-400 text-left">
                <tr>
                    <th class="px-4 py-2 font-medium">{{ $t('col_agent') }}</th>
                    <th class="px-4 py-2 font-medium">{{ $t('col_queues') }}</th>
                    <th class="px-4 py-2 font-medium text-right">{{ $t('col_offered') }}</th>
                    <th class="px-4 py-2 font-medium text-right">{{ $t('answer_rate') }}</th>
                    <th class="px-4 py-2 font-medium">{{ $t('col_findings') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($agents['data'] ?? [] as $agent)
                    <tr wire:key="agent-{{ $agent['dn'] }}" x-data="{ open: false }" class="align-top">
                        <td class="px-4 py-2">
                            <span class="font-medium text-gray-800 dark:text-gray-200">{{ $agent['dn'] }} {{ $agent['name'] ?? '' }}</span>
                            <span class="block text-xs text-gray-400">
                                {{ $agent['current_profile'] ?? '' }}
                                @if (($agent['enabled'] ?? true) === false) · <span class="text-red-600">{{ $t('disabled') }}</span> @endif
                            </span>
                        </td>
                        <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-300">
                            <button type="button" x-on:click="open = ! open" class="hover:underline">{{ $t('queues_count', ['count' => $agent['queues_count'] ?? 0]) }}</button>
                            <ul x-show="open" x-collapse x-cloak class="mt-1 space-y-0.5">
                                @foreach ($agent['queues'] ?? [] as $q)
                                    <li>
                                        {{ $q['queue'] }} {{ $q['name'] ?? '' }}
                                        <span @class(['text-emerald-600' => ($q['status_at_backup'] ?? null) === 'LoggedIn', 'text-gray-400' => ($q['status_at_backup'] ?? null) !== 'LoggedIn'])>({{ $q['status_at_backup'] ?? '?' }})</span>
                                        @if (isset($q['offered'])) · {{ $q['answered'] ?? 0 }}/{{ $q['offered'] }} @endif
                                        @if (isset($q['connected_hours'])) · {{ $q['connected_hours'] }} h @endif
                                    </li>
                                @endforeach
                            </ul>
                        </td>
                        <td class="px-4 py-2 text-right text-gray-600 dark:text-gray-300">{{ $agent['offered'] ?? '—' }}</td>
                        <td @class(['px-4 py-2 text-right', 'text-red-600 dark:text-red-400' => isset($agent['answer_rate']) && $agent['answer_rate'] < 0.5, 'text-gray-600 dark:text-gray-300' => ! isset($agent['answer_rate']) || $agent['answer_rate'] >= 0.5])>
                            {{ isset($agent['answer_rate']) ? round($agent['answer_rate'] * 100, 1).' %' : '—' }}
                        </td>
                        <td class="px-4 py-2">
                            @forelse ($agent['findings'] ?? [] as $finding)
                                <button type="button" wire:click="showFindingsFor('{{ $agent['dn'] }}')"
                                        @class(['mr-1 mb-1 inline-flex rounded-full px-2 py-0.5 text-[11px] font-medium ring-1', $this->categoryBadge($finding['category'] ?? '')])
                                        title="{{ $finding['finding'] ?? '' }}">
                                    {{ $this->codeLabel($finding['code'] ?? '') }}
                                </button>
                            @empty
                                <span class="text-xs text-gray-400">—</span>
                            @endforelse
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">{{ $t('no_agents_found') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('expert-statistics::livewire.config-audit.pagination', ['meta' => $agents['meta'] ?? [], 'list' => 'agents'])
</div>
