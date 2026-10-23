<x-expert-statistics::cluster-layout
    :title="__('expert-statistics::pbx.config_audit.title')"
    :subtitle="__('expert-statistics::pbx.config_audit.subtitle')"
>

@php
    $t = fn (string $key, array $replace = []) => __('expert-statistics::pbx.config_audit.'.$key, $replace);
    $audit = $summary['audit'] ?? [];
    $statsMode = ($summary['mode'] ?? null) === 'statistics';
@endphp

<div class="space-y-5">

    {{-- ── Header ─────────────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-linear-to-br from-teal-600 to-purple-500 flex items-center justify-center shrink-0 shadow-sm">
                <x-heroicon-o-adjustments-horizontal class="w-5 h-5 text-white" />
            </div>
            @if ($state === 'ok' && ! empty($audit))
                <div class="text-xs text-gray-500 dark:text-gray-400 space-y-0.5">
                    <p>
                        {{ $t('backup_of', ['date' => ! empty($audit['backup_date']) ? \Carbon\Carbon::parse($audit['backup_date'])->isoFormat('LL LT') : '—']) }}
                        @if (! empty($audit['pbx_version'])) · 3CX {{ $audit['pbx_version'] }} @endif
                    </p>
                    <p class="flex items-center gap-1.5">
                        <span @class([
                            'inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium ring-1',
                            'bg-teal-50 text-teal-700 ring-teal-200 dark:bg-teal-950/30 dark:text-teal-400 dark:ring-teal-900' => $statsMode,
                            'bg-gray-50 text-gray-600 ring-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700' => ! $statsMode,
                        ])>
                            {{ $statsMode ? $t('mode_statistics') : $t('mode_configuration_only') }}
                        </span>
                        @if (! empty($summary['period']['start']))
                            <span>{{ \Carbon\Carbon::parse($summary['period']['start'])->isoFormat('L') }} – {{ \Carbon\Carbon::parse($summary['period']['end'])->isoFormat('L') }}</span>
                        @endif
                    </p>
                </div>
            @endif
        </div>

        <div class="flex flex-wrap items-center gap-2">
            {{-- Period --}}
            <form wire:submit="applyPeriod" class="flex items-center gap-1.5">
                <input type="date" wire:model="startDate" aria-label="{{ $t('period_from') }}"
                       class="rounded-lg border-gray-200 dark:border-gray-700 dark:bg-gray-800 text-xs py-1.5" />
                <span class="text-gray-400 text-xs">→</span>
                <input type="date" wire:model="endDate" aria-label="{{ $t('period_to') }}"
                       class="rounded-lg border-gray-200 dark:border-gray-700 dark:bg-gray-800 text-xs py-1.5" />
                <button type="submit" class="px-3 py-1.5 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-xs font-medium">{{ $t('apply') }}</button>
                @if ($startDate || $endDate)
                    <button type="button" wire:click="resetPeriod" class="text-xs text-gray-500 hover:underline">{{ $t('last_30_days') }}</button>
                @endif
            </form>

            @if ($state === 'ok')
                <button wire:click="downloadReport"
                        class="inline-flex items-center gap-1.5 px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm text-xs font-medium text-gray-600 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                    <x-heroicon-o-document-arrow-down class="w-4 h-4" />
                    {{ $t('download_report') }}
                </button>
            @endif

            <button wire:click="refresh" wire:loading.attr="disabled" wire:target="refresh"
                    class="inline-flex items-center gap-1.5 px-4 py-2 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50">
                <x-heroicon-o-arrow-path class="w-4 h-4" wire:loading.class="animate-spin" wire:target="refresh" />
                {{ $t('refresh') }}
            </button>
        </div>
    </div>
    @error('period') <p class="text-xs text-red-600">{{ $message }}</p> @enderror

    {{-- ── States ─────────────────────────────────────────────────────────── --}}
    @if ($state === 'not_activated')
        <div class="rounded-xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900 px-4 py-3 text-sm text-amber-800 dark:text-amber-300">
            {{ $t('not_activated') }}
        </div>
    @elseif ($state === 'empty')
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 px-6 py-12 text-center">
            <x-heroicon-o-document-magnifying-glass class="w-10 h-10 mx-auto text-gray-300 dark:text-gray-600" />
            <p class="mt-3 text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('empty_title') }}</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $t('empty_help') }}</p>
        </div>
    @elseif ($state === 'error')
        <div class="rounded-xl bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-900 px-4 py-3 text-sm text-red-700 dark:text-red-400">
            {{ $errorMessage }}
        </div>
    @else
        {{-- ── Tabs ───────────────────────────────────────────────────────── --}}
        <nav class="flex flex-wrap gap-1 border-b border-gray-200 dark:border-gray-700" aria-label="{{ $t('title') }}">
            @foreach (\CXEngine\ExpertStatistics\Livewire\ConfigAudit\ConfigAudit::TABS as $name)
                <button wire:click="selectTab('{{ $name }}')" wire:key="tab-{{ $name }}"
                        @class([
                            'px-3 py-2 text-sm font-medium -mb-px border-b-2 transition-colors',
                            'border-teal-500 text-teal-700 dark:text-teal-400' => $tab === $name,
                            'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' => $tab !== $name,
                        ])>
                    {{ $t('tabs.'.$name) }}
                </button>
            @endforeach
        </nav>

        <div wire:loading.flex wire:target="refresh, selectTab, applyPeriod, resetPeriod" class="items-center justify-center gap-1.5 py-16">
            <span class="w-2.5 h-2.5 bg-teal-400 rounded-full animate-bounce [animation-delay:0ms]"></span>
            <span class="w-2.5 h-2.5 bg-teal-400 rounded-full animate-bounce [animation-delay:150ms]"></span>
            <span class="w-2.5 h-2.5 bg-teal-400 rounded-full animate-bounce [animation-delay:300ms]"></span>
        </div>

        <div wire:loading.remove wire:target="refresh, selectTab, applyPeriod, resetPeriod">
            @include('expert-statistics::livewire.config-audit.'.$tab)
        </div>
    @endif
</div>

</x-expert-statistics::cluster-layout>
