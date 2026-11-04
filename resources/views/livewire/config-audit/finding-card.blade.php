{{-- One recommendation: what is wrong, what to change in 3CX, and the evidence. --}}
@php
    $impact = (float) ($finding['score_impact'] ?? 0);
    $stats = collect($finding['stats'] ?? [])->except('period')->filter(fn ($v) => is_scalar($v));
    $dids = $finding['did'] ?? [];
    $accepted = $finding['accepted'] ?? null;
    [$objectType, $objectNumber] = array_pad(explode(':', (string) ($finding['object'] ?? ''), 2), 2, '');
    $objectName = $finding['object_label'] ?? $finding['object'] ?? '';
@endphp

<div wire:key="{{ $key }}" x-data="{ open: false, accepting: false, note: '' }" data-finding-object="{{ $finding['object'] ?? '' }}"
     @class([
         'bg-white dark:bg-gray-800 rounded-lg shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-4',
         'opacity-80' => $accepted,
     ])>
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

    @if ($accepted)
        @php $acceptedDate = ! empty($accepted['accepted_at']) ? \Carbon\Carbon::parse($accepted['accepted_at'])->isoFormat('L') : '—'; @endphp
        <div class="mt-2 rounded-md bg-gray-50 dark:bg-gray-900/40 border border-gray-200 dark:border-gray-700 px-3 py-2 text-sm text-gray-600 dark:text-gray-300 flex flex-wrap items-start justify-between gap-2">
            <div>
                <span class="font-medium">
                    {{ ! empty($accepted['accepted_by'])
                        ? $t('accepted_by', ['name' => $accepted['accepted_by'], 'date' => $acceptedDate])
                        : $t('accepted_on', ['date' => $acceptedDate]) }}
                </span>
                @if (! empty($accepted['note']))
                    <span class="block italic">« {{ $accepted['note'] }} »</span>
                @endif
            </div>
            @if ($this->canModify() && ! empty($accepted['id']))
                <button type="button" wire:click="revokeAcceptance({{ (int) $accepted['id'] }})" class="text-xs text-teal-600 hover:underline whitespace-nowrap">
                    {{ $t('report_again') }}
                </button>
            @endif
        </div>
    @elseif (! empty($finding['proposed_action']))
        <div class="mt-2 rounded-md bg-teal-50 dark:bg-teal-950/30 border border-teal-100 dark:border-teal-900 px-3 py-2 text-sm text-teal-800 dark:text-teal-300">
            <span class="font-medium">{{ $t('what_to_change') }}</span> {{ $finding['proposed_action'] }}
        </div>
    @endif

    <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
        <span>{{ $t('object') }}:
            @if ($objectType === 'queue')
                <button type="button" wire:click="showQueue(@js($objectNumber))" class="text-teal-600 hover:underline">{{ $objectName }}</button>
            @elseif ($objectType === 'extension')
                <button type="button" wire:click="showAgent(@js($objectNumber))" class="text-teal-600 hover:underline">{{ $objectName }}</button>
            @elseif ($objectType === 'inbound_rule' && ! str_starts_with($objectNumber, 'default:'))
                <button type="button" wire:click="showRoute(@js($objectNumber), @js($t('tabs.'.$tab)))" class="text-teal-600 hover:underline">{{ $objectName }}</button>
            @else
                <span @class(['font-mono' => empty($finding['object_label'])])>{{ $objectName }}</span>
            @endif
        </span>
        @if (count($dids))
            <span>{{ $t('dids') }}:
                @foreach (array_slice($dids, 0, 3) as $did)
                    <button type="button" wire:click="showRoute(@js($did), @js($t('tabs.'.$tab)))" class="text-teal-600 hover:underline">{{ $did }}</button>{{ ! $loop->last ? ',' : '' }}
                @endforeach
                {{ count($dids) > 3 ? ' (+'.(count($dids) - 3).')' : '' }}
            </span>
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
        @if (! $accepted && $this->canModify())
            <button type="button" x-show="! accepting" x-on:click="accepting = true" class="ml-auto text-gray-500 hover:text-teal-600 hover:underline" title="{{ $t('mark_expected_help') }}">
                {{ $t('mark_expected') }}
            </button>
        @endif
    </div>

    @if (! empty($finding['evidence']))
        <p x-show="open" x-collapse x-cloak class="mt-2 text-xs text-gray-500 dark:text-gray-400 font-mono break-words">{{ $finding['evidence'] }}</p>
    @endif

    @if (! $accepted && $this->canModify())
        <div x-show="accepting" x-collapse x-cloak class="mt-3 space-y-2">
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $t('mark_expected_help') }}</p>
            <textarea x-model="note" rows="2" maxlength="1000" placeholder="{{ $t('expected_note') }}"
                      class="w-full rounded-lg border-gray-200 dark:border-gray-700 dark:bg-gray-900 bg-white text-gray-900 placeholder:text-gray-400 dark:text-gray-100 dark:placeholder:text-gray-500 dark:[color-scheme:dark] text-sm"></textarea>
            <div class="flex gap-2">
                <button type="button" x-on:click="$wire.acceptFinding(@js($finding['id'] ?? ''), note)"
                        class="rounded-lg bg-teal-600 px-3 py-1 text-sm font-medium text-white hover:bg-teal-700">{{ $t('confirm') }}</button>
                <button type="button" x-on:click="accepting = false; note = ''"
                        class="rounded-lg px-3 py-1 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">{{ $t('cancel') }}</button>
            </div>
        </div>
    @endif
</div>
