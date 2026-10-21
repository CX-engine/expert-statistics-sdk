{{-- One recommendation: what is wrong, what to change in 3CX, and the evidence. --}}
@php
    $impact = (float) ($finding['score_impact'] ?? 0);
    $stats = collect($finding['stats'] ?? [])->except('period')->filter(fn ($v) => is_scalar($v));
    $dids = $finding['did'] ?? [];
@endphp

<div wire:key="{{ $key }}" x-data="{ open: false }"
     class="bg-white dark:bg-gray-800 rounded-lg shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-4">
    <div class="flex flex-wrap items-start justify-between gap-2">
        <div class="flex flex-wrap items-center gap-2">
            <span @class(['inline-flex rounded-full px-2 py-0.5 text-[11px] font-medium ring-1', $this->categoryBadge($finding['category'] ?? '')])>
                {{ $t('categories.'.($finding['category'] ?? 'optimisation')) }}
            </span>
            <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $this->codeLabel($finding['code'] ?? '') }}</span>
            @if (! empty($finding['area']))
                <span class="text-[11px] text-gray-400">{{ $t('areas.'.$finding['area']) }}</span>
            @endif
        </div>
        @if ($impact > 0)
            <span class="text-xs font-semibold text-red-600 dark:text-red-400 whitespace-nowrap" title="{{ $t('score_impact_help') }}">
                −{{ number_format($impact, $impact < 1 ? 2 : 1) }} {{ $t('points') }}
            </span>
        @endif
    </div>

    <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">{{ $finding['finding'] ?? '' }}</p>

    @if (! empty($finding['proposed_action']))
        <div class="mt-2 rounded-md bg-teal-50 dark:bg-teal-950/30 border border-teal-100 dark:border-teal-900 px-3 py-2 text-sm text-teal-800 dark:text-teal-300">
            <span class="font-medium">{{ $t('what_to_change') }}</span> {{ $finding['proposed_action'] }}
        </div>
    @endif

    <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
        <span>{{ $t('object') }}: <span class="font-mono">{{ $finding['object'] ?? '' }}</span></span>
        @if (count($dids))
            <span>{{ $t('dids') }}: {{ implode(', ', array_slice($dids, 0, 3)) }}{{ count($dids) > 3 ? ' (+'.(count($dids) - 3).')' : '' }}</span>
        @endif
        @foreach ($stats as $name => $value)
            <span class="rounded bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5">{{ str_replace('_', ' ', $name) }}: {{ is_float($value) && $value < 1 ? round($value * 100, 1).' %' : $value }}</span>
        @endforeach
        @if (! empty($finding['evidence']))
            <button type="button" x-on:click="open = ! open" class="text-teal-600 hover:underline">
                <span x-show="! open">{{ $t('show_evidence') }}</span>
                <span x-show="open" x-cloak>{{ $t('hide_evidence') }}</span>
            </button>
        @endif
    </div>

    @if (! empty($finding['evidence']))
        <p x-show="open" x-collapse x-cloak class="mt-2 text-xs text-gray-500 dark:text-gray-400 font-mono break-words">{{ $finding['evidence'] }}</p>
    @endif
</div>
