@php
    $score = isset($summary['score']) ? (float) $summary['score'] : null;
    $coverage = isset($summary['coverage']) ? round((float) $summary['coverage'] * 100) : null;
    $circumference = 2 * M_PI * 52;
    $byCategory = $summary['counts']['by_category'] ?? [];
@endphp

<div class="space-y-5">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        {{-- Score --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-5 flex flex-col items-center">
            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('score') }}</p>
            <div class="relative w-36 h-36 mt-3">
                <svg viewBox="0 0 120 120" class="w-full h-full -rotate-90" aria-hidden="true">
                    <circle cx="60" cy="60" r="52" fill="none" stroke-width="10" class="stroke-gray-100 dark:stroke-gray-700" />
                    @if ($score !== null)
                        <circle cx="60" cy="60" r="52" fill="none" stroke-width="10" stroke-linecap="round"
                                stroke-dasharray="{{ $circumference }}"
                                stroke-dashoffset="{{ $circumference * (1 - $score / 100) }}"
                                @class(['stroke-current', $this->scoreColor($score)]) />
                    @endif
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center">
                    <span @class(['text-3xl font-bold', $this->scoreColor($score)])>{{ $score !== null ? number_format($score, 1) : '—' }}<span class="text-lg">%</span></span>
                </div>
            </div>
            <p class="mt-3 text-xs text-center text-gray-500 dark:text-gray-400">{{ $t('score_help') }}</p>
        </div>

        {{-- Areas --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-5">
            <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">{{ $t('areas_title') }}</p>
            <div class="space-y-3">
                @foreach ($summary['areas'] ?? [] as $area => $values)
                    @php $areaScore = isset($values['score']) ? (float) $values['score'] : null; @endphp
                    <button type="button" wire:click="showFindings('', '{{ $area }}')" wire:key="area-{{ $area }}" class="w-full text-left group">
                        <div class="flex justify-between text-xs">
                            <span class="text-gray-600 dark:text-gray-300 group-hover:underline">{{ $t('areas.'.$area) }}</span>
                            <span class="text-gray-500 dark:text-gray-400">
                                {{ $areaScore !== null ? number_format($areaScore, 1).' %' : '—' }}
                                <span class="text-gray-400">· {{ $values['findings'] ?? 0 }}</span>
                            </span>
                        </div>
                        <div class="mt-1 h-2 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                            <div @class(['h-full rounded-full', $this->scoreBarColor($areaScore)]) style="width: {{ max(0, min(100, $areaScore ?? 0)) }}%"></div>
                        </div>
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Key figures --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-5 space-y-4">
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $t('coverage') }}</p>
                <p class="text-2xl font-bold text-gray-800 dark:text-gray-200">{{ $coverage !== null ? $coverage.' %' : '—' }}</p>
                <p class="text-xs text-gray-400 dark:text-gray-500">{{ $t('coverage_help') }}</p>
            </div>
            @if (isset($summary['total_inbound_calls']))
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $t('inbound_calls') }}</p>
                    <p class="text-2xl font-bold text-gray-800 dark:text-gray-200">{{ number_format((int) $summary['total_inbound_calls'], 0, ',', ' ') }}</p>
                </div>
            @endif
            <div class="flex flex-wrap gap-2">
                @foreach (['probable_error', 'to_check', 'optimisation'] as $category)
                    <button type="button" wire:click="showFindings('{{ $category }}')"
                            @class(['inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium ring-1', $this->categoryBadge($category)])>
                        <span class="font-bold">{{ $byCategory[$category] ?? 0 }}</span> {{ $t('categories.'.$category) }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Not analysed --}}
    @if (! empty($summary['missing_inputs']))
        <div class="rounded-lg bg-gray-50 dark:bg-gray-800/60 border border-gray-200 dark:border-gray-700 px-4 py-3 text-xs text-gray-600 dark:text-gray-400">
            <p class="font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('missing_title') }}</p>
            <ul class="list-disc pl-5 space-y-0.5">
                @foreach (collect($summary['missing_inputs'])->groupBy(fn ($m) => explode(':', $m, 2)[0]) as $kind => $items)
                    <li>
                        {{ $t('missing.'.$kind) !== 'expert-statistics::pbx.config_audit.missing.'.$kind ? $t('missing.'.$kind) : $kind }}
                        @php $objects = $items->map(fn ($m) => explode(':', $m, 2)[1] ?? null)->filter(); @endphp
                        @if ($objects->isNotEmpty())
                            <span class="text-gray-400">({{ $objects->take(8)->implode(', ') }}{{ $objects->count() > 8 ? ', …' : '' }})</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Top recommendations --}}
    <div>
        <div class="flex items-center justify-between mb-2">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ $t('top_recommendations') }}</h3>
            <button wire:click="selectTab('recommendations')" class="text-xs text-teal-600 hover:underline">{{ $t('see_all') }}</button>
        </div>
        <div class="space-y-3">
            @forelse ($summary['top_recommendations'] ?? [] as $finding)
                @include('expert-statistics::livewire.config-audit.finding-card', ['finding' => $finding, 'key' => 'top-'.$loop->index])
            @empty
                <div class="bg-white dark:bg-gray-800 rounded-lg ring-1 ring-gray-950/5 dark:ring-white/10 px-4 py-6 text-center text-sm text-emerald-600 dark:text-emerald-400">
                    {{ $t('no_findings') }}
                </div>
            @endforelse
        </div>
    </div>

    {{-- Trend --}}
    @include('expert-statistics::livewire.config-audit.trend')
</div>
