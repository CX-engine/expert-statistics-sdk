@php
    $alertLevelLabels = __('expert-statistics::pbx.config.wallboard.alertLevels');
    $alertLevelClasses = [
        'green' => ['text' => 'text-emerald-600 dark:text-emerald-400', 'ring' => 'ring-emerald-500/30', 'bg' => 'bg-emerald-500/5', 'dt' => 'text-emerald-700/60 dark:text-emerald-300/50', 'icon' => 'heroicon-o-check-circle'],
        'orange' => ['text' => 'text-amber-600 dark:text-amber-400', 'ring' => 'ring-amber-500/30', 'bg' => 'bg-amber-500/5', 'dt' => 'text-amber-700/60 dark:text-amber-300/50', 'icon' => 'heroicon-o-exclamation-triangle'],
        'red' => ['text' => 'text-rose-600 dark:text-rose-400', 'ring' => 'ring-rose-500/30', 'bg' => 'bg-rose-500/5', 'dt' => 'text-rose-700/60 dark:text-rose-300/50', 'icon' => 'heroicon-o-x-circle'],
    ];
    $fmtTile = function (array $t) use ($alertLevelLabels): string {
        $v = $t['value'] ?? null;
        if ($v === null) return '—';
        if (($t['unit'] ?? '') === 'level') return $alertLevelLabels[$v] ?? $v;
        if (($t['unit'] ?? '') === 'seconds') {
            $s = max(0, (int) round((float) $v)); $h = intdiv($s, 3600); $m = intdiv($s % 3600, 60); $sec = $s % 60;
            return $h > 0 ? sprintf('%d:%02d:%02d', $h, $m, $sec) : sprintf('%d:%02d', $m, $sec);
        }
        if (($t['unit'] ?? '') === 'percent') return $v . '%';
        return number_format((float) $v);
    };
    $previewCols = (int) ($previewMeta['layout']['columns'] ?? 3);
    $translatedMetricLabels = __('expert-statistics::pbx.config.wallboard.metricLabels');
    $metricLabels = collect($wallboardMetrics)->mapWithKeys(fn ($m) => [$m['key'] => $translatedMetricLabels[$m['key']] ?? $m['label']]);

    // Alert-level tiles are not rendered as cards in the live preview: the worst
    // level tints the whole board instead (lighter page, darker cards, dark text).
    $levelSeverity = ['green' => 0, 'orange' => 1, 'red' => 2];
    $wallboardThemes = [
        'green' => ['page' => 'bg-emerald-100', 'card' => 'border-emerald-500/40 bg-emerald-300', 'label' => 'text-emerald-800', 'value' => 'text-emerald-950', 'badge' => 'bg-emerald-950/10 text-emerald-900 ring-emerald-950/20'],
        'orange' => ['page' => 'bg-amber-100', 'card' => 'border-amber-500/40 bg-amber-300', 'label' => 'text-amber-800', 'value' => 'text-amber-950', 'badge' => 'bg-amber-950/10 text-amber-900 ring-amber-950/20'],
        'red' => ['page' => 'bg-red-100', 'card' => 'border-red-500/50 bg-red-400', 'label' => 'text-red-900', 'value' => 'text-red-950', 'badge' => 'bg-red-950/10 text-red-900 ring-red-950/20'],
    ];
    $worstLevel = function (array $tiles) use ($levelSeverity): ?string {
        $level = null;
        foreach ($tiles as $t) {
            if (($t['unit'] ?? '') !== 'level' || empty($t['value'])) continue;
            if ($level === null || ($levelSeverity[$t['value']] ?? -1) > ($levelSeverity[$level] ?? -1)) $level = $t['value'];
        }
        return $level;
    };
    $previewLevel = $worstLevel($previewTiles ?? []);
    $previewTheme = $previewLevel ? ($wallboardThemes[$previewLevel] ?? null) : null;
    $previewDisplayTiles = array_values(array_filter($previewTiles ?? [], fn ($t) => ($t['unit'] ?? '') !== 'level'));
@endphp

@if (! $this->activeHostName())
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-6 text-center">
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('expert-statistics::pbx.config.noHost') }}</p>
    </div>
@else
    <div class="space-y-6">

        {{-- Live preview --}}
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm" wire:poll.10s="refreshWallboardPreview">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 dark:border-gray-700 px-4 py-3">
                <div class="flex items-center gap-2">
                    <span class="inline-block h-2.5 w-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                        {{ __('expert-statistics::pbx.config.wallboard.livePreview') }}
                        @if (! empty($previewMeta['name'])) — {{ $previewMeta['name'] }} @endif
                    </h2>
                </div>
                @if (count($wallboards) > 0)
                    <div class="flex items-center gap-2">
                        <select wire:model.live="selectedWallboardUuid" wire:change="selectWallboard($event.target.value)"
                            class="rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 dark:text-white text-sm focus:ring-primary-500 focus:border-primary-500">
                            @foreach ($wallboards as $wb)
                                <option value="{{ $wb['uuid'] }}">{{ $wb['name'] }}{{ ($wb['is_default'] ?? false) ? ' ★' : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>

            <div class="p-4 rounded-b-xl {{ $previewTheme['page'] ?? '' }}">
                @if (empty($previewTiles))
                    <p class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('expert-statistics::pbx.config.wallboard.noData') }}</p>
                @else
                    <div class="grid gap-3" style="grid-template-columns: repeat({{ max(1, min(6, $previewCols)) }}, minmax(0, 1fr));">
                        @foreach ($previewDisplayTiles as $tile)
                            <div class="rounded-xl border p-4 {{ $previewTheme ? $previewTheme['card'] : 'border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50' }}">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="truncate text-lg font-semibold {{ $previewTheme ? $previewTheme['label'] : 'text-gray-500 dark:text-gray-400' }}">
                                        {{ $metricLabels[$tile['metric'] ?? ''] ?? $tile['title'] ?? $tile['metric'] }}
                                    </div>
                                    @if (! empty($tile['queue']))
                                        <span class="ml-2 shrink-0 rounded-md px-2 py-0.5 text-sm font-semibold tabular-nums ring-1 ring-inset {{ $previewTheme ? $previewTheme['badge'] : 'bg-gray-900/5 text-gray-500 ring-gray-900/10 dark:bg-white/10 dark:text-gray-300 dark:ring-white/10' }}">#{{ $tile['queue'] }}</span>
                                    @endif
                                </div>
                                <div class="mt-2 text-4xl sm:text-5xl font-extrabold tabular-nums {{ $previewTheme ? $previewTheme['value'] : 'text-gray-900 dark:text-white' }}">
                                    {{ $fmtTile($tile) }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- AI builder --}}
        @php $aiResultCols = (int) ($wallboardAiResult['layout']['columns'] ?? 3); @endphp
        <div class="relative overflow-hidden rounded-xl border border-purple-200/70 dark:border-purple-900/40 bg-gradient-to-br from-purple-50 via-white to-primary-50 dark:from-purple-950/30 dark:via-gray-800 dark:to-primary-950/20 shadow-sm">
            <div class="pointer-events-none absolute -right-20 -top-20 h-56 w-56 rounded-full bg-purple-300/20 blur-3xl dark:bg-purple-600/10"></div>
            <div class="pointer-events-none absolute -bottom-24 -left-16 h-56 w-56 rounded-full bg-primary-300/20 blur-3xl dark:bg-primary-600/10"></div>

            <div class="relative space-y-4 p-5">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-purple-500 to-primary-500 text-white shadow-sm">
                        <x-heroicon-m-sparkles class="h-5 w-5" />
                    </div>
                    <div class="min-w-0">
                        <h2 class="flex items-center gap-2 text-base font-semibold text-gray-900 dark:text-white">
                            {{ __('expert-statistics::pbx.config.wallboard.aiTitle') }}
                            <span class="inline-flex items-center rounded-full bg-purple-100 dark:bg-purple-900/50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-purple-700 dark:text-purple-300">
                                {{ __('expert-statistics::pbx.config.wallboard.aiBadge') }}
                            </span>
                        </h2>
                        <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{{ __('expert-statistics::pbx.config.wallboard.aiHint') }}</p>
                    </div>
                </div>

                <div class="relative" wire:loading.class="opacity-60 pointer-events-none" wire:target="buildWallboardWithAi">
                    <textarea wire:model="wallboardPrompt" rows="3" placeholder="{{ __('expert-statistics::pbx.config.wallboard.aiPlaceholder') }}"
                        class="block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white/80 dark:bg-gray-900/60 backdrop-blur-sm dark:text-white shadow-sm text-sm focus:border-purple-400 focus:ring-purple-400"></textarea>
                </div>

                @php
                    $aiExamples = [
                        __('expert-statistics::pbx.config.wallboard.aiExample1') => __('expert-statistics::pbx.config.wallboard.aiExample1Prompt'),
                        __('expert-statistics::pbx.config.wallboard.aiExample2') => __('expert-statistics::pbx.config.wallboard.aiExample2Prompt'),
                        __('expert-statistics::pbx.config.wallboard.aiExample3') => __('expert-statistics::pbx.config.wallboard.aiExample3Prompt'),
                    ];
                @endphp
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs font-medium text-gray-400 dark:text-gray-500">{{ __('expert-statistics::pbx.config.wallboard.aiExamplesLabel') }}</span>
                    @foreach ($aiExamples as $label => $prompt)
                        <button type="button" wire:click="$set('wallboardPrompt', {{ Js::from($prompt) }})"
                            class="group inline-flex items-center gap-1 rounded-full border border-purple-200 dark:border-purple-900/50 bg-white/70 dark:bg-gray-900/40 px-3 py-1 text-xs font-medium text-purple-700 dark:text-purple-300 transition-colors hover:border-purple-300 hover:bg-purple-50 dark:hover:bg-purple-900/30">
                            <x-heroicon-m-sparkles class="h-3 w-3 opacity-60 group-hover:opacity-100" />
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                <div class="flex justify-end">
                    <button wire:click="buildWallboardWithAi" wire:loading.attr="disabled" wire:target="buildWallboardWithAi" type="button" @disabled(! $this->canModify())
                        class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 hover:bg-primary-700 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors disabled:opacity-60">
                        <x-heroicon-m-sparkles class="w-4 h-4" wire:loading.remove wire:target="buildWallboardWithAi" />
                        <span wire:loading.remove wire:target="buildWallboardWithAi">{{ __('expert-statistics::pbx.config.wallboard.aiGenerate') }}</span>
                        <span wire:loading wire:target="buildWallboardWithAi" class="inline-flex items-center gap-1.5">
                            <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            {{ __('expert-statistics::pbx.config.wallboard.aiGenerating') }}
                        </span>
                    </button>
                </div>

                <div wire:loading.grid wire:target="buildWallboardWithAi" class="gap-2" style="grid-template-columns: repeat(3, minmax(0, 1fr));">
                    @for ($i = 0; $i < 3; $i++)
                        <div class="animate-pulse rounded-xl border border-gray-200 dark:border-gray-700 bg-white/60 dark:bg-gray-900/40 p-4">
                            <div class="h-3 w-2/3 rounded bg-gray-200 dark:bg-gray-700"></div>
                            <div class="mt-3 h-6 w-1/2 rounded bg-gray-200 dark:bg-gray-700"></div>
                        </div>
                    @endfor
                </div>

                {{-- Result preview --}}
                @if ($wallboardAiResult)
                    <div wire:loading.remove wire:target="buildWallboardWithAi"
                        class="rounded-xl border border-primary-200 dark:border-primary-800/60 bg-white/80 dark:bg-gray-900/50 backdrop-blur-sm shadow-sm">
                        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-primary-100 dark:border-primary-900/40 px-4 py-3">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 text-xs font-medium uppercase tracking-wide text-primary-600 dark:text-primary-400">
                                    <x-heroicon-m-check-badge class="h-4 w-4" />
                                    {{ __('expert-statistics::pbx.config.wallboard.aiResultTitle') }}
                                </div>
                                <div class="mt-1 truncate text-base font-semibold text-gray-900 dark:text-white">{{ $wallboardAiResult['name'] ?? __('expert-statistics::pbx.config.wallboard.aiDefaultName') }}</div>
                                @if (! empty($wallboardAiResult['description']))
                                    <div class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $wallboardAiResult['description'] }}</div>
                                @endif
                            </div>
                            <div class="flex items-center gap-2">
                                <button wire:click="discardAiWallboard" type="button"
                                    class="inline-flex items-center gap-1 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <x-heroicon-m-x-mark class="h-3.5 w-3.5" />
                                    {{ __('expert-statistics::pbx.config.wallboard.discard') }}
                                </button>
                                <button wire:click="saveAiWallboard" type="button" @disabled(! $this->canModify())
                                    class="inline-flex items-center gap-1 rounded-lg bg-primary-600 hover:bg-primary-700 px-3 py-1.5 text-xs font-medium text-white shadow-sm disabled:opacity-50 disabled:cursor-not-allowed">
                                    <x-heroicon-m-check class="h-3.5 w-3.5" />
                                    {{ __('expert-statistics::pbx.config.wallboard.saveAi') }}
                                </button>
                            </div>
                        </div>
                        <div class="p-4">
                            <div class="grid gap-2" style="grid-template-columns: repeat({{ max(1, min(6, $aiResultCols)) }}, minmax(0, 1fr));">
                                @foreach (($wallboardAiResult['tiles'] ?? []) as $tile)
                                    @php
                                        $isAlert = ($tile['unit'] ?? '') === 'level' && ! empty($tile['value']);
                                        $alertStyle = $isAlert ? ($alertLevelClasses[$tile['value']] ?? $alertLevelClasses['green']) : null;
                                    @endphp
                                    @if ($isAlert)
                                        <div class="rounded-xl border border-gray-200 dark:border-gray-700/60 {{ $alertStyle['bg'] }} ring-1 {{ $alertStyle['ring'] }} p-4" style="grid-column: span {{ min(2, max(1, $aiResultCols)) }};">
                                            <div class="flex items-center justify-between gap-2">
                                                <div class="truncate text-xs font-medium text-gray-500 dark:text-gray-400">{{ $metricLabels[$tile['metric'] ?? ''] ?? $tile['title'] ?? $tile['metric'] }}</div>
                                                @if (! empty($tile['queue']))<span class="ml-2 shrink-0 rounded-md bg-gray-900/5 dark:bg-white/10 px-1.5 py-0.5 text-[11px] font-semibold tabular-nums text-gray-500 dark:text-gray-300 ring-1 ring-inset ring-gray-900/10 dark:ring-white/10">#{{ $tile['queue'] }}</span>@endif
                                            </div>
                                            <div class="mt-1 flex items-center gap-1.5 text-lg font-extrabold {{ $alertStyle['text'] }}">
                                                <x-dynamic-component :component="$alertStyle['icon']" class="h-5 w-5 shrink-0" />
                                                {{ $fmtTile($tile) }}
                                            </div>
                                        </div>
                                    @else
                                        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 p-4">
                                            <div class="flex items-center justify-between gap-2">
                                                <div class="truncate text-xs font-medium text-gray-500 dark:text-gray-400">{{ $metricLabels[$tile['metric'] ?? ''] ?? $tile['title'] ?? $tile['metric'] }}</div>
                                                @if (! empty($tile['queue']))<span class="ml-2 shrink-0 rounded-md bg-gray-900/5 dark:bg-white/10 px-1.5 py-0.5 text-[11px] font-semibold tabular-nums text-gray-500 dark:text-gray-300 ring-1 ring-inset ring-gray-900/10 dark:ring-white/10">#{{ $tile['queue'] }}</span>@endif
                                            </div>
                                            <div class="mt-2 text-2xl font-extrabold tabular-nums text-gray-900 dark:text-white">{{ $fmtTile($tile) }}</div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Editor form --}}
        @if ($showWallboardForm)
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm p-4 space-y-4">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                    {{ $editingWallboardUuid ? __('expert-statistics::pbx.config.wallboard.editTitle') : __('expert-statistics::pbx.config.wallboard.createTitle') }}
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('expert-statistics::pbx.config.wallboard.name') }}</label>
                        <input type="text" wire:model="wallboardForm.name"
                            class="block w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 dark:text-white shadow-sm text-sm focus:ring-primary-500 focus:border-primary-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('expert-statistics::pbx.config.wallboard.description') }}</label>
                        <input type="text" wire:model="wallboardForm.description"
                            class="block w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 dark:text-white shadow-sm text-sm focus:ring-primary-500 focus:border-primary-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('expert-statistics::pbx.config.wallboard.columns') }}</label>
                        <input type="number" min="1" max="6" wire:model="wallboardForm.columns"
                            class="block w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 dark:text-white shadow-sm text-sm focus:ring-primary-500 focus:border-primary-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('expert-statistics::pbx.config.wallboard.refresh') }}</label>
                        <input type="number" min="2" max="300" wire:model="wallboardForm.refresh_seconds"
                            class="block w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 dark:text-white shadow-sm text-sm focus:ring-primary-500 focus:border-primary-500" />
                    </div>
                </div>

                {{-- Tiles --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('expert-statistics::pbx.config.wallboard.cards') }}</label>
                    <div class="space-y-2">
                        @forelse ($wallboardForm['tiles'] as $i => $t)
                            <div class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-2 text-sm">
                                <span class="text-gray-800 dark:text-gray-200">
                                    {{ $metricLabels[$t['metric']] ?? $t['metric'] }}
                                    @if (! empty($t['queue']))<span class="text-gray-400 dark:text-gray-500">· #{{ $t['queue'] }}</span>@else<span class="text-gray-400 dark:text-gray-500">· {{ __('expert-statistics::pbx.config.wallboard.allQueues') }}</span>@endif
                                </span>
                                <button wire:click="removeTileFromForm({{ $i }})" type="button" class="text-red-600 hover:text-red-700 text-xs font-semibold">
                                    {{ __('expert-statistics::pbx.config.wallboard.remove') }}
                                </button>
                            </div>
                        @empty
                            <p class="text-xs text-gray-400 dark:text-gray-500">{{ __('expert-statistics::pbx.config.wallboard.noCards') }}</p>
                        @endforelse
                    </div>

                    <div class="mt-3 flex flex-wrap items-end gap-2">
                        <div>
                            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ __('expert-statistics::pbx.config.wallboard.metric') }}</label>
                            <select wire:model="newTileMetric"
                                class="rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 dark:text-white text-sm focus:ring-primary-500 focus:border-primary-500">
                                <option value="">—</option>
                                @foreach ($wallboardMetrics as $m)
                                    <option value="{{ $m['key'] }}">{{ $metricLabels[$m['key']] ?? $m['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ __('expert-statistics::pbx.config.wallboard.queue') }}</label>
                            <select wire:model="newTileQueue"
                                class="rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 dark:text-white text-sm focus:ring-primary-500 focus:border-primary-500">
                                <option value="">{{ __('expert-statistics::pbx.config.wallboard.allQueues') }}</option>
                                @foreach ($wallboardQueues as $q)
                                    <option value="{{ $q['queue_dn'] }}">{{ $q['queue_dn'] }} - {{ $q['label'] ?? $q['queue_dn'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button wire:click="addTileToForm" type="button"
                            class="inline-flex items-center rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            {{ __('expert-statistics::pbx.config.wallboard.addCard') }}
                        </button>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button wire:click="cancelWallboardForm" type="button"
                        class="inline-flex items-center rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        {{ __('expert-statistics::pbx.config.wallboard.cancel') }}
                    </button>
                    <button wire:click="saveWallboard" type="button" @disabled(! $this->canModify())
                        class="inline-flex items-center rounded-lg bg-primary-600 hover:bg-primary-700 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        {{ __('expert-statistics::pbx.config.save') }}
                    </button>
                </div>
            </div>
        @endif

        {{-- Wallboard list --}}
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 px-4 py-3">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                    {{ __('expert-statistics::pbx.config.wallboard.listTitle') }}
                    <span class="text-xs font-normal text-gray-400 dark:text-gray-500">({{ count($wallboards) }}/{{ $wallboardMax }})</span>
                </h2>
                <button wire:click="openCreateWallboardForm" type="button" @disabled(! $this->canModify() || count($wallboards) >= $wallboardMax)
                    class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 hover:bg-primary-700 px-3 py-1.5 text-sm font-medium text-white shadow-sm transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    {{ __('expert-statistics::pbx.config.wallboard.new') }}
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm text-left">
                    <thead class="bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-2">{{ __('expert-statistics::pbx.config.wallboard.name') }}</th>
                            <th class="px-4 py-2">{{ __('expert-statistics::pbx.config.wallboard.status') }}</th>
                            <th class="px-4 py-2 text-right">{{ __('expert-statistics::pbx.config.wallboard.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($wallboards as $wb)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $wb['name'] }}</div>
                                    <div class="flex gap-1 mt-1">
                                        @if ($wb['is_default'] ?? false)
                                            <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-semibold bg-primary-100 dark:bg-primary-900 text-primary-700 dark:text-primary-200">{{ __('expert-statistics::pbx.config.wallboard.default') }}</span>
                                        @endif
                                        @if (($wb['source'] ?? '') === 'ai')
                                            <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-semibold bg-purple-100 dark:bg-purple-900 text-purple-700 dark:text-purple-200">AI</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <button wire:click="toggleWallboardActive('{{ $wb['uuid'] }}')" type="button" @disabled(! $this->canModify()) @class([
                                        'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold',
                                        'bg-emerald-100 dark:bg-emerald-900 text-emerald-700 dark:text-emerald-200' => $wb['active'] ?? false,
                                        'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400' => ! ($wb['active'] ?? false),
                                        'disabled:cursor-not-allowed disabled:opacity-75' => ! $this->canModify(),
                                    ])>
                                        {{ ($wb['active'] ?? false) ? __('expert-statistics::pbx.config.wallboard.active') : __('expert-statistics::pbx.config.wallboard.inactive') }}
                                    </button>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-2 flex-wrap">
                                        <button wire:click="selectWallboard('{{ $wb['uuid'] }}')" type="button" class="text-xs font-semibold text-primary-600 hover:text-primary-700">
                                            {{ __('expert-statistics::pbx.config.wallboard.preview') }}
                                        </button>
                                        <button x-data="{ copied: false, copy(text) {
                                                const done = () => { this.copied = true; setTimeout(() => this.copied = false, 1500); };
                                                if (navigator.clipboard && window.isSecureContext) {
                                                    navigator.clipboard.writeText(text).then(done).catch(() => {});
                                                    return;
                                                }
                                                const ta = document.createElement('textarea');
                                                ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0';
                                                document.body.appendChild(ta); ta.focus(); ta.select();
                                                try { document.execCommand('copy'); done(); } catch (e) {}
                                                document.body.removeChild(ta);
                                            } }" @click="copy('{{ $this->shareUrl($wb['share_key'] ?? '') }}')" type="button"
                                            class="text-xs font-semibold text-gray-600 dark:text-gray-300 hover:text-gray-800">
                                            <span x-show="! copied">{{ __('expert-statistics::pbx.config.wallboard.copyLink') }}</span>
                                            <span x-show="copied" x-cloak class="text-emerald-600">{{ __('expert-statistics::pbx.config.wallboard.linkCopied') }}</span>
                                        </button>
                                        <a href="{{ $this->shareUrl($wb['share_key'] ?? '') }}" target="_blank"
                                            class="text-xs font-semibold text-gray-600 dark:text-gray-300 hover:text-gray-800">{{ __('expert-statistics::pbx.config.wallboard.open') }}</a>
                                        @if ($this->canModify())
                                            <button wire:click="openEditWallboard('{{ $wb['uuid'] }}')" type="button" class="text-xs font-semibold text-gray-600 dark:text-gray-300 hover:text-gray-800">
                                                {{ __('expert-statistics::pbx.config.wallboard.edit') }}
                                            </button>
                                            @if ($wb['is_default'] ?? false)
                                                <button wire:click="revertWallboard('{{ $wb['uuid'] }}')" wire:confirm="{{ __('expert-statistics::pbx.config.wallboard.confirmRevert') }}" type="button"
                                                    class="text-xs font-semibold text-amber-600 hover:text-amber-700">{{ __('expert-statistics::pbx.config.wallboard.revert') }}</button>
                                            @else
                                                <button wire:click="deleteWallboard('{{ $wb['uuid'] }}')" wire:confirm="{{ __('expert-statistics::pbx.config.wallboard.confirmDelete') }}" type="button"
                                                    class="text-xs font-semibold text-red-600 hover:text-red-700">{{ __('expert-statistics::pbx.config.delete') }}</button>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                    {{ __('expert-statistics::pbx.config.wallboard.noCards') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif
