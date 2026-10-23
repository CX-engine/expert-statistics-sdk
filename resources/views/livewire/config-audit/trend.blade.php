{{--
    Score per backup: one series over time → a thin line with markers, a muted
    0/50/100 grid and the 70 % / 90 % reference lines (the score colour bands).
    Only the latest point carries a direct label; every point has a hover
    tooltip (date, score, change). The History tab is the table view.
--}}
@php
    $trend = array_values(array_filter($summary['trend'] ?? [], fn ($p) => isset($p['score'])));
    $count = count($trend);

    // Plot geometry (SVG units; the SVG scales to the card width).
    [$width, $height] = [640, 200];
    [$left, $right, $top, $bottom] = [36, 52, 14, 30];
    $plotW = $width - $left - $right;
    $plotH = $height - $top - $bottom;
    $x = fn (int $i) => $count > 1 ? $left + $plotW * $i / ($count - 1) : $left + $plotW / 2;
    $y = fn (float $score) => $top + $plotH * (1 - max(0, min(100, $score)) / 100);

    $points = [];
    foreach ($trend as $i => $p) {
        $score = (float) $p['score'];
        $previous = $i > 0 ? (float) $trend[$i - 1]['score'] : null;
        $points[] = [
            'x' => round($x($i), 1),
            'y' => round($y($score), 1),
            'score' => $score,
            'date' => ! empty($p['backup_date']) ? \Carbon\Carbon::parse($p['backup_date'])->isoFormat('L') : '—',
            'short' => ! empty($p['backup_date']) ? \Carbon\Carbon::parse($p['backup_date'])->isoFormat('D/MM') : '',
            'delta' => $previous !== null ? round($score - $previous, 1) : null,
        ];
    }

    $last = $points[$count - 1] ?? null;
    $first = $points[0] ?? null;
    $change = $count > 1 ? round($last['score'] - $first['score'], 1) : null;
    // Date labels: all of them when they fit, otherwise about 6 evenly spaced.
    $labelEvery = max(1, (int) ceil($count / 6));
    $line = implode(' ', array_map(fn ($p) => $p['x'].','.$p['y'], $points));
@endphp

<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-5">
    <div class="flex flex-wrap items-baseline justify-between gap-2 mb-3">
        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('trend') }}</p>
        @if ($change !== null)
            <p class="text-xs text-gray-500 dark:text-gray-400">
                <span @class([
                    'font-semibold',
                    'text-emerald-600 dark:text-emerald-400' => $change > 0,
                    'text-red-600 dark:text-red-400' => $change < 0,
                ])>{{ $change > 0 ? '+' : '' }}{{ number_format($change, 1) }} {{ $t('points') }}</span>
                {{ $t('trend_since', ['date' => $first['date'], 'count' => $count]) }}
            </p>
        @endif
    </div>

    @if ($count < 2)
        <p class="text-xs text-gray-500 dark:text-gray-400 py-6 text-center">{{ $t('trend_single') }}</p>
    @else
        <div class="relative" x-data="{ i: null }" x-on:mouseleave="i = null">
            <svg viewBox="0 0 {{ $width }} {{ $height }}" class="w-full h-auto overflow-visible" role="img"
                 aria-label="{{ $t('trend') }}: {{ number_format($first['score'], 1) }} % → {{ number_format($last['score'], 1) }} %">

                {{-- Grid 0 / 50 / 100 --}}
                @foreach ([0, 50, 100] as $tick)
                    <line x1="{{ $left }}" x2="{{ $width - $right }}" y1="{{ $y($tick) }}" y2="{{ $y($tick) }}"
                          class="stroke-gray-300 dark:stroke-gray-600" stroke-width="1" />
                    <text x="{{ $left - 8 }}" y="{{ $y($tick) + 4 }}" text-anchor="end" class="fill-gray-400 dark:fill-gray-500" font-size="11">{{ $tick }}</text>
                @endforeach

                {{-- Reference lines: score colour bands --}}
                @foreach ([90, 70] as $ref)
                    <line x1="{{ $left }}" x2="{{ $width - $right }}" y1="{{ $y($ref) }}" y2="{{ $y($ref) }}"
                          class="stroke-gray-400 dark:stroke-gray-500" stroke-width="1" stroke-dasharray="4 4" />
                    <text x="{{ $width - $right + 6 }}" y="{{ $y($ref) + 4 }}" class="fill-gray-400 dark:fill-gray-500" font-size="11">{{ $ref }} %</text>
                @endforeach

                {{-- Series --}}
                <polyline points="{{ $line }}" fill="none" class="stroke-teal-600 dark:stroke-teal-400"
                          stroke-width="2" stroke-linejoin="round" stroke-linecap="round" />

                @foreach ($points as $i => $p)
                    {{-- Hover column, wider than the marker --}}
                    <rect x="{{ $p['x'] - $plotW / max(1, $count - 1) / 2 }}" y="{{ $top }}" width="{{ $plotW / max(1, $count - 1) }}" height="{{ $plotH }}"
                          fill="transparent" x-on:mouseenter="i = {{ $i }}" />
                    <line x1="{{ $p['x'] }}" x2="{{ $p['x'] }}" y1="{{ $top }}" y2="{{ $top + $plotH }}"
                          class="stroke-gray-400 dark:stroke-gray-500" stroke-width="1" x-show="i === {{ $i }}" x-cloak style="display: none" />
                    <circle cx="{{ $p['x'] }}" cy="{{ $p['y'] }}" r="{{ $i === $count - 1 ? 5 : 4 }}"
                            class="fill-teal-600 dark:fill-teal-400 stroke-white dark:stroke-gray-800" stroke-width="2" pointer-events="none" />
                    @if ($i % $labelEvery === 0 || $i === $count - 1)
                        <text x="{{ $p['x'] }}" y="{{ $height - 8 }}" text-anchor="middle" class="fill-gray-400 dark:fill-gray-500" font-size="11">{{ $p['short'] }}</text>
                    @endif
                @endforeach

                {{-- Direct label on the latest point only --}}
                <text x="{{ $last['x'] }}" y="{{ $last['y'] - 12 }}" text-anchor="middle" font-size="12" font-weight="600"
                      class="fill-gray-700 dark:fill-gray-200">{{ number_format($last['score'], 1) }} %</text>
            </svg>

            {{-- Tooltip --}}
            @foreach ($points as $i => $p)
                {{-- Edge points anchor their tooltip inwards so it stays inside the card --}}
                <div x-show="i === {{ $i }}" x-cloak
                     @class([
                         'pointer-events-none absolute -translate-y-full whitespace-nowrap rounded-md bg-gray-900/90 dark:bg-gray-700 px-2.5 py-1.5 text-xs text-white shadow',
                         '-translate-x-1/2' => $i > 0 && $i < $count - 1,
                         '-translate-x-full' => $i === $count - 1,
                     ])
                     style="display: none; left: {{ round($p['x'] / $width * 100, 2) }}%; top: calc({{ round($p['y'] / $height * 100, 2) }}% - 10px);">
                    <p class="font-semibold">{{ number_format($p['score'], 1) }} %</p>
                    <p class="text-gray-300">{{ $t('trend_backup', ['date' => $p['date']]) }}</p>
                    @if ($p['delta'] !== null)
                        <p class="text-gray-300">{{ $p['delta'] > 0 ? '+' : '' }}{{ number_format($p['delta'], 1) }} {{ $t('points') }} {{ $t('trend_vs_previous') }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
