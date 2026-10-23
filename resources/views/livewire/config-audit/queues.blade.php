<div class="space-y-3">
    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $t('queues_help') }}</p>

    @forelse ($queues as $queue)
        @php
            $stats = $queue['stats'] ?? null;
            $categories = collect($queue['findings'] ?? [])->countBy('category');
        @endphp
        <div wire:key="queue-{{ $queue['number'] }}" x-data="{ open: false }"
             class="bg-white dark:bg-gray-800 rounded-lg shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
            <button type="button" x-on:click="open = ! open" class="w-full text-left px-4 py-3 flex flex-wrap items-center gap-x-6 gap-y-2">
                <div class="min-w-48">
                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $queue['number'] }} – {{ $queue['name'] ?? '' }}</p>
                    <p class="text-xs text-gray-400">{{ $queue['strategy'] ?? '—' }}@if (! empty($queue['department'])) · {{ $queue['department'] }} @endif</p>
                </div>
                <div class="text-xs text-gray-600 dark:text-gray-300">
                    <span class="font-semibold">{{ $queue['agents_logged_in'] ?? 0 }}</span>/{{ $queue['agents'] ?? 0 }} {{ $t('agents_logged_in') }}
                </div>
                @if ($stats)
                    <div class="text-xs text-gray-600 dark:text-gray-300 flex gap-4">
                        <span>{{ $t('calls_count', ['count' => $stats['offered'] ?? 0]) }}</span>
                        <span>{{ $t('answer_rate') }} {{ isset($stats['answer_rate']) ? round($stats['answer_rate'] * 100, 1).' %' : '—' }}</span>
                        <span @class(['text-red-600 dark:text-red-400' => ($stats['abandon_rate'] ?? 0) > 0.15])>{{ $t('abandon_rate') }} {{ isset($stats['abandon_rate']) ? round($stats['abandon_rate'] * 100, 1).' %' : '—' }}</span>
                        <span>{{ $t('avg_wait') }} {{ isset($stats['avg_wait']) ? round($stats['avg_wait']).' s' : '—' }}</span>
                    </div>
                @endif
                <div class="ml-auto flex items-center gap-2">
                    @foreach (['probable_error', 'to_check', 'optimisation'] as $category)
                        @if (($categories[$category] ?? 0) > 0)
                            <span @class(['inline-flex rounded-full px-2 py-0.5 text-[11px] font-medium ring-1', $this->categoryBadge($category)])>{{ $categories[$category] }}</span>
                        @endif
                    @endforeach
                    @if (($queue['score_impact'] ?? 0) > 0)
                        <span class="text-xs font-semibold text-red-600 dark:text-red-400">−{{ number_format($queue['score_impact'], 1) }} {{ $t('points') }}</span>
                    @endif
                    <x-heroicon-o-chevron-down class="w-4 h-4 text-gray-400 transition-transform" x-bind:class="open && 'rotate-180'" />
                </div>
            </button>

            <div x-show="open" x-collapse x-cloak class="border-t border-gray-100 dark:border-gray-700 px-4 py-3 space-y-3 text-xs text-gray-600 dark:text-gray-300">
                <div class="flex flex-wrap gap-x-6 gap-y-1">
                    <span>{{ $t('ring_time') }}: {{ $queue['ring_time'] ?? '—' }} s</span>
                    <span>{{ $t('max_wait') }}: {{ $queue['max_wait'] ?? '—' }} s</span>
                    <span>{{ $t('wrap_up') }}: {{ $queue['wrap_up'] ?? 0 }} s</span>
                    <span>{{ $t('overflow') }}: {{ $queue['overflow_label'] ?? '—' }}</span>
                    <span>{{ $t('hours') }}: {{ $queue['hours'] ?? '—' }}</span>
                    <span>{{ $t('callback') }}: {{ ! empty($queue['callback']) ? '✓' : '✗' }}</span>
                </div>
                <div>
                    <p class="font-medium text-gray-700 dark:text-gray-200 mb-1">{{ $t('agents_title') }}</p>
                    <div class="flex flex-wrap gap-1.5">
                        @forelse ($queue['members'] ?? [] as $member)
                            <span @class([
                                'rounded px-1.5 py-0.5',
                                'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400' => ($member['status'] ?? null) === 'LoggedIn',
                                'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400' => ($member['status'] ?? null) !== 'LoggedIn',
                            ])>{{ $member['dn'] }} {{ $member['name'] ?? '' }}</span>
                        @empty
                            <span class="text-red-600">{{ $t('no_agents') }}</span>
                        @endforelse
                    </div>
                </div>
                @if (! empty($queue['findings']))
                    <button wire:click="showFindingsFor('queue:{{ $queue['number'] }}')" class="text-teal-600 hover:underline">
                        {{ $t('see_queue_findings', ['count' => count($queue['findings'])]) }}
                    </button>
                @endif
            </div>
        </div>
    @empty
        <div class="bg-white dark:bg-gray-800 rounded-lg ring-1 ring-gray-950/5 dark:ring-white/10 px-4 py-8 text-center text-sm text-gray-500">{{ $t('no_queues') }}</div>
    @endforelse
</div>
