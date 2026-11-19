@php
    $backupDate = ! empty($audit['backup_date']) ? \Carbon\Carbon::parse($audit['backup_date'])->isoFormat('L LT') : '—';
@endphp
<div class="space-y-4">
    <input type="search" wire:model.live.debounce.400ms="agentSearch" placeholder="{{ $t('search_agents') }}"
           class="w-full sm:w-80 rounded-lg border-gray-200 dark:border-gray-700 dark:bg-gray-800 bg-white text-gray-900 placeholder:text-gray-400 dark:text-gray-100 dark:placeholder:text-gray-500 dark:[color-scheme:dark] text-sm py-1.5" />

    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $t('statuses_help', ['date' => $backupDate]) }}</p>

    <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-lg shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-900/40 text-xs text-gray-500 dark:text-gray-400 text-left">
                <tr>
                    <th class="px-4 py-2 font-medium">{{ $t('col_agent') }}</th>
                    <th class="px-4 py-2 font-medium">{{ $t('col_queues') }}</th>
                    <th class="px-4 py-2 font-medium text-right" title="{{ $t('col_offered_help') }}">{{ $t('col_offered') }}</th>
                    <th class="px-4 py-2 font-medium text-right" title="{{ $t('answer_rate_agent_help') }}">{{ $t('answer_rate') }}</th>
                    <th class="px-4 py-2 font-medium">{{ $t('col_findings') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($agents['data'] ?? [] as $agent)
                    @php
                        $focused = $focus === (string) $agent['dn'];
                        $agentLabel = trim($agent['dn'].' '.($agent['name'] ?? ''));
                    @endphp
                    <tr wire:key="agent-{{ $agent['dn'] }}" x-data="{ open: @js($focused) }"
                        @if ($focused) x-init="$el.scrollIntoView({ block: 'center' })" @endif
                        @class(['align-top', 'bg-teal-50/60 dark:bg-teal-950/20' => $focused])>
                        <td class="px-4 py-2">
                            <span class="font-medium text-gray-800 dark:text-gray-200">{{ $agentLabel }}</span>
                            <span class="block text-xs text-gray-400" title="{{ $t('statuses_help', ['date' => $backupDate]) }}">
                                @if (! empty($agent['current_profile'])){{ $t('profile_at_backup', ['status' => $agent['current_profile']]) }}@endif
                                @if (($agent['enabled'] ?? true) === false) · <span class="text-red-600">{{ $t('disabled') }}</span> @endif
                            </span>
                        </td>
                        <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-300">
                            <button type="button" x-on:click="open = ! open" class="hover:underline">{{ $t('queues_count', ['count' => $agent['queues_count'] ?? 0]) }}</button>
                            <ul x-show="open" x-collapse x-cloak class="mt-1 space-y-0.5">
                                @foreach ($agent['queues'] ?? [] as $q)
                                    <li>
                                        <button type="button" wire:click="showQueue(@js((string) $q['queue']), @js($agentLabel))" class="text-teal-600 hover:underline">{{ $q['queue'] }} – {{ $q['name'] ?? '' }}</button>
                                        <span @class(['text-emerald-600' => ($q['status_at_backup'] ?? null) === 'LoggedIn', 'text-gray-400' => ($q['status_at_backup'] ?? null) !== 'LoggedIn'])>({{ match ($q['status_at_backup'] ?? null) {
                                            'LoggedIn' => $t('logged_in_at_backup'),
                                            'LoggedOut' => $t('logged_out_at_backup'),
                                            default => $q['status_at_backup'] ?? '?',
                                        } }})</span>
                                        @if (isset($q['offered'])) · <span title="{{ $t('col_offered_help') }}">{{ $t('queue_answer_detail', ['answered' => $q['answered'] ?? 0, 'offered' => $q['offered']]) }}</span> @endif
                                        @if (isset($q['connected_hours'])) · <span title="{{ $t('connected_hours_help') }}">{{ $q['connected_hours'] }} h</span> @endif
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
                                <button type="button" wire:click="showFindingsFor(@js((string) $agent['dn']), @js($agentLabel))"
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
