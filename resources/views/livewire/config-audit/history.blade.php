<div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-lg shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
    <table class="min-w-full text-sm">
        <thead class="bg-gray-50 dark:bg-gray-900/40 text-xs text-gray-500 dark:text-gray-400 text-left">
            <tr>
                <th class="px-4 py-2 font-medium">{{ $t('col_backup') }}</th>
                <th class="px-4 py-2 font-medium text-right">{{ $t('score') }}</th>
                <th class="px-4 py-2 font-medium">{{ $t('col_based_on') }}</th>
                <th class="px-4 py-2 font-medium">{{ $t('col_configuration_findings') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            @forelse ($history as $audit)
                @php $score = isset($audit['score']) ? (float) $audit['score'] : null; @endphp
                <tr wire:key="audit-{{ $audit['id'] ?? $loop->index }}">
                    <td class="px-4 py-2 text-gray-700 dark:text-gray-300">
                        {{ ! empty($audit['backup_date']) ? \Carbon\Carbon::parse($audit['backup_date'])->isoFormat('LL LT') : '—' }}
                        <span class="block text-xs text-gray-400">{{ $audit['backup_file'] ?? '' }}@if (! empty($audit['pbx_version'])) · 3CX {{ $audit['pbx_version'] }} @endif</span>
                    </td>
                    <td @class(['px-4 py-2 text-right font-semibold', $this->scoreColor($score)])>{{ $score !== null ? number_format($score, 1).' %' : '—' }}</td>
                    <td class="px-4 py-2 text-xs text-gray-500 dark:text-gray-400">
                        {{ ($audit['mode'] ?? null) === 'statistics' ? $t('mode_statistics') : $t('mode_configuration_only') }}
                        @if (! empty($audit['period']['start'])) · {{ $audit['period']['start'] }} → {{ $audit['period']['end'] }} @endif
                    </td>
                    <td class="px-4 py-2">
                        @foreach (['probable_error', 'to_check', 'optimisation'] as $category)
                            <span @class(['mr-1 inline-flex rounded-full px-2 py-0.5 text-[11px] font-medium ring-1', $this->categoryBadge($category)])>{{ $audit['configuration_findings'][$category] ?? 0 }}</span>
                        @endforeach
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="px-4 py-8 text-center text-gray-500">{{ $t('no_history') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
