@php
    // Display-only shaping, mirroring the inline-PHP-block convention
    // already used for the wallboard live preview in
    // resources/views/livewire/configuration/_wallboard.blade.php - kept
    // out of the Livewire component since none of it is state, just view
    // logic derived from $wallboard/$tiles on every render.

    $columns = max(1, min(6, (int) ($wallboard['layout']['columns'] ?? 3)));

    $metricLabels = __('expert-statistics::pbx.config.wallboard.metricLabels');

    $fmtValue = function (array $tile): string {
        $v = $tile['value'] ?? null;
        if ($v === null) {
            return '—';
        }
        if (($tile['unit'] ?? null) === 'seconds') {
            $total = max(0, (int) round((float) $v));
            $h = intdiv($total, 3600);
            $m = intdiv($total % 3600, 60);
            $s = $total % 60;

            return $h > 0 ? sprintf('%d:%02d:%02d', $h, $m, $s) : sprintf('%d:%02d', $m, $s);
        }
        if (($tile['unit'] ?? null) === 'percent') {
            return $v.'%';
        }

        return number_format((float) $v);
    };

    // Accent waiting/abandon tiles when they climb, so the board reads at a glance.
    $accentClass = function (array $tile): string {
        $metric = $tile['metric'] ?? null;
        $value = (float) ($tile['value'] ?? 0);
        if (in_array($metric, ['calls_waiting', 'abandoned'], true) && $value > 0) {
            return 'accent-amber';
        }
        if ($metric === 'abandon_rate_pct' && $value >= 10) {
            return 'accent-rose';
        }
        if ($metric === 'sla_pct') {
            return $value >= 80 ? 'accent-emerald' : 'accent-amber';
        }

        return '';
    };

    // Alert-level tiles are not rendered as cards: the worst level tints
    // the whole page instead (lighter page, darker cards, dark text).
    $levelSeverity = ['green' => 0, 'orange' => 1, 'red' => 2];
    $level = null;
    foreach ($tiles as $tile) {
        if (($tile['unit'] ?? null) !== 'level' || empty($tile['value'])) {
            continue;
        }
        if ($level === null || ($levelSeverity[$tile['value']] ?? -1) > ($levelSeverity[$level] ?? -1)) {
            $level = $tile['value'];
        }
    }

    $displayTiles = array_values(array_filter($tiles, fn (array $tile): bool => ($tile['unit'] ?? null) !== 'level'));
@endphp
{{-- Single root element: the <html> document shell lives in the layout, since Livewire morphs this root on every poll. --}}
<div class="wb-page {{ $level ? 'level-'.$level : '' }}" wire:poll.{{ $pollSeconds }}s="refresh">
    {{-- Chromeless: no navigation, no header — only the wallboard. --}}
    <div class="wb-wrap">
        <div class="wb-topbar">
            <div>
                <h1 class="wb-heading">{{ $wallboard['name'] ?? 'Wallboard' }}</h1>
                @if (! empty($wallboard['description']))
                    <p class="wb-desc">{{ $wallboard['description'] }}</p>
                @endif
            </div>
            <div class="wb-status">
                <span class="wb-dot {{ $error ? 'is-error' : 'is-live' }}"></span>
                {{-- Formatted in the browser so it shows the viewer's local time (the server runs in UTC). --}}
                <span x-text="$wire.lastUpdatedAt ? new Date($wire.lastUpdatedAt).toLocaleTimeString() : '—'"></span>
            </div>
        </div>

        @if ($error)
            <div class="wb-message">
                {{ $error === 'inactive'
                    ? __('expert-statistics::pbx.config.wallboard.publicInactive')
                    : __('expert-statistics::pbx.config.wallboard.publicNotFound') }}
            </div>
        @elseif (empty($displayTiles))
            <div class="wb-message">
                {{ __('expert-statistics::pbx.config.wallboard.noData') }}
            </div>
        @else
            <div class="wb-grid" style="grid-template-columns: repeat({{ $columns }}, minmax(0, 1fr));">
                @foreach ($displayTiles as $tile)
                    <div class="wb-tile">
                        <div class="wb-tile-header">
                            <div class="wb-tile-label">
                                {{ $metricLabels[$tile['metric'] ?? ''] ?? $tile['title'] ?? $tile['label'] ?? $tile['metric'] ?? '' }}
                            </div>
                            @if (! empty($tile['queue']))
                                <span class="wb-tile-badge">#{{ $tile['queue'] }}</span>
                            @endif
                        </div>
                        <div class="wb-tile-value {{ $accentClass($tile) }}">
                            {{ $fmtValue($tile) }}
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
