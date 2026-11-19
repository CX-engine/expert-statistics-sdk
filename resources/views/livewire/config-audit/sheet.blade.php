{{--
    Call flow sheet of a DID (expert-stats RouteSheet; diagram-output.md of the
    3cx-config-audit skill). The only connectors are entry → first → decision;
    destinations are named in the columns and grouped below, so lines never cross.
    The palette is in a scoped style block so it does not depend on the host's
    Tailwind build (arbitrary colours would need a rebuild of every host app).
--}}
@php
    $headerOf = fn (?string $type) => match ($type) {
        'call_flow_app' => 'cfd', 'ivr' => 'ivr', 'queue', 'ring_group' => 'queue', default => 'other',
    };
    $withFindings = collect($routeDetail['findings'] ?? [])->pluck('object')->flip();
    $scrollTo = "(o) => { const c = [...document.querySelectorAll('[data-finding-object]')].find(e => e.dataset.findingObject === o); if (c) { c.scrollIntoView({ behavior: 'smooth', block: 'center' }); c.style.outline = '3px solid #14b8a6'; c.style.outlineOffset = '2px'; setTimeout(() => { c.style.outline = ''; c.style.outlineOffset = ''; }, 2000); } }";
    $columns = count($sheet['columns'] ?? []);
    // Recommendations of each object, shown on hover (and focus) next to the pointer.
    $tips = collect($routeDetail['findings'] ?? [])->groupBy('object')->map(fn ($findings) => $findings->map(fn ($f) => [
        'badge' => $this->categoryBadge($f['category'] ?? ''),
        'category' => $t('categories.'.($f['category'] ?? 'optimisation')),
        'label' => $this->codeLabel($f['code'] ?? ''),
        'text' => \Illuminate\Support\Str::limit($f['finding'] ?? '', 200),
    ])->values()->all())->all();
    $hover = fn (?string $object) => $object !== null && isset($tips[$object])
        ? 'x-on:mouseenter="show('.e(json_encode($object)).', $event)" x-on:mouseleave="tip = null" x-on:focus="show('.e(json_encode($object)).', $event)" x-on:blur="tip = null"'
        : '';
@endphp

@once
<style>
    .xs-sheet { --xs-bg: #F4F7FB; --xs-card: #FFFFFF; --xs-text: #18324A; --xs-muted: #5B6B80; --xs-ring: rgba(11, 42, 74, .12);
        --xs-hours: #EAF7F0; --xs-fallback: #FDE8E8; --xs-fallback-ring: #F3B4B4; --xs-watch: #FFF4E5; --xs-watch-title: #A85A12; --xs-arrow: #6B7C93;
        position: relative; background: var(--xs-bg); color: var(--xs-text); border-radius: .75rem; box-shadow: 0 0 0 1px var(--xs-ring); }
    .dark .xs-sheet { --xs-bg: #1f2937; --xs-card: #111827; --xs-text: #e5e7eb; --xs-muted: #9ca3af; --xs-ring: rgba(255, 255, 255, .1);
        --xs-hours: #10261b; --xs-fallback: #2a1414; --xs-fallback-ring: #7f1d1d; --xs-watch: #2a1c0c; --xs-watch-title: #fbbf24; --xs-arrow: #9ca3af; }
    .xs-sheet .xs-title { border-radius: .75rem .75rem 0 0; background: #0B2A4A; color: #fff; padding: .75rem 1rem; font-size: 1.05rem; font-weight: 700; letter-spacing: .02em; }
    .xs-sheet .xs-body { padding: 1rem; display: flex; flex-direction: column; gap: 1.25rem; }
    .xs-sheet .xs-top { display: grid; grid-template-columns: 1fr; gap: .75rem; }
    @media (min-width: 1024px) { .xs-sheet .xs-top { grid-template-columns: 1fr auto 1fr auto 1fr; align-items: stretch; } }
    .xs-sheet .xs-arrow { display: none; color: var(--xs-arrow); align-items: center; }
    @media (min-width: 1024px) { .xs-sheet .xs-arrow { display: flex; } }
    .xs-sheet .xs-grid { display: grid; grid-template-columns: 1fr; gap: .75rem; }
    @media (min-width: 768px) { .xs-sheet .xs-grid.c2, .xs-sheet .xs-grid.c3, .xs-sheet .xs-grid.dest { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (min-width: 1280px) { .xs-sheet .xs-grid.c3 { grid-template-columns: repeat(3, minmax(0, 1fr)); } .xs-sheet .xs-grid.dest { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
    .xs-sheet .xs-card { background: var(--xs-card); border-radius: .5rem; overflow: hidden; box-shadow: 0 0 0 1px var(--xs-ring), 0 1px 2px rgba(0, 0, 0, .05); }
    .xs-sheet .xs-card.hours { background: var(--xs-hours); }
    .xs-sheet .xs-card.fallback { background: var(--xs-fallback); box-shadow: 0 0 0 1px var(--xs-fallback-ring); }
    .xs-sheet .xs-head { padding: .4rem .75rem; font-size: .875rem; font-weight: 600; color: #fff; display: flex; justify-content: space-between; gap: .5rem; }
    .xs-sheet .xs-lines { padding: .5rem .75rem; font-size: 13px; line-height: 1.4; display: flex; flex-direction: column; gap: .15rem; }
    .xs-sheet .xs-note { font-style: italic; color: var(--xs-muted); padding-top: .25rem; }
    .xs-sheet .xs-h { margin-bottom: .5rem; font-size: .75rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--xs-muted); }
    .xs-sheet .xs-flag { margin-left: .25rem; font-weight: 700; color: #d97706; cursor: pointer; }
    .xs-sheet .xs-head .xs-flag { color: #fde68a; }
    .xs-sheet .xs-flag:hover, .xs-sheet .xs-watch button:hover { text-decoration: underline; }
    .xs-sheet .xs-watch { background: var(--xs-watch); box-shadow: 0 0 0 1px #E58B2A; border-radius: .5rem; padding: .75rem 1rem; }
    .xs-sheet .xs-watch h4 { font-size: .875rem; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; color: var(--xs-watch-title); }
    .xs-sheet .xs-watch ul { margin-top: .4rem; display: flex; flex-direction: column; gap: .25rem; font-size: 13px; }
    .xs-sheet .xs-watch li { display: flex; gap: .5rem; }
    .xs-sheet .xs-watch button { text-align: left; }
    .xs-sheet .xs-dot { margin-top: .4rem; height: .5rem; width: .5rem; flex-shrink: 0; border-radius: 9999px; background: #9ca3af; }
    .xs-sheet .xs-dot.probable_error { background: #ef4444; } .xs-sheet .xs-dot.to_check { background: #f59e0b; } .xs-sheet .xs-dot.optimisation { background: #0ea5e9; }
    .xs-sheet .xs-footer { font-size: 11px; color: var(--xs-muted); }
    .xs-sheet .h-entry { background: #0B2A4A; } .xs-sheet .h-hours { background: #3AA76D; } .xs-sheet .h-cfd { background: #7C5CC4; }
    .xs-sheet .h-ivr { background: #14A3C7; } .xs-sheet .h-queue, .xs-sheet .h-blue { background: #2B8ACD; } .xs-sheet .h-other { background: #5B6B80; }
    .xs-sheet .h-green { background: #2E7D5B; } .xs-sheet .h-orange { background: #C26A12; } .xs-sheet .h-red, .xs-sheet .h-fallback { background: #D94A4A; }
</style>
@endonce

<div class="xs-sheet" x-data="{
        go: {{ $scrollTo }},
        tips: @js($tips),
        tip: null, tipX: 0, tipY: 0,
        // Next to the pointer (or the element, from the keyboard), kept inside the sheet.
        show(id, event) {
            const box = this.$root.getBoundingClientRect();
            const at = event.clientX ? { x: event.clientX, y: event.clientY } : (({ left, bottom }) => ({ x: left, y: bottom }))(event.target.getBoundingClientRect());
            this.tip = id;
            this.tipX = Math.max(8, Math.min(at.x - box.left + 14, box.width - 340));
            this.tipY = at.y - box.top + 14;
        },
    }">
    <div class="xs-title">{{ $sheet['title'] }}</div>

    <div class="xs-body">
        {{-- Top row: the only connectors --}}
        <div class="xs-top">
            @foreach ([[$sheet['entry'], 'entry'], [$sheet['first'], $headerOf($sheet['first']['type'] ?? null)], [$sheet['decision'], 'hours']] as $i => [$block, $header])
                @if ($block)
                    @if ($i > 0)
                        <div class="xs-arrow" aria-hidden="true">
                            <svg width="34" height="14" viewBox="0 0 34 14"><path d="M0 7h28" stroke="currentColor" stroke-width="2.5"/><path d="M26 2l7 5-7 5z" fill="currentColor"/></svg>
                        </div>
                    @endif
                    <div @class(['xs-card', 'hours' => $i === 2])>
                        <div class="xs-head h-{{ $header }}">{{ $block['title'] }}</div>
                        <div class="xs-lines">
                            @foreach ($block['lines'] as $line)
                                <p>{{ $line }}</p>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        </div>

        {{-- Branches --}}
        @if ($columns > 0)
            <div>
                <h4 class="xs-h">{{ $t('sheet_branches') }}</h4>
                <div @class(['xs-grid', 'c2' => $columns === 2, 'c3' => $columns >= 3])>
                    @foreach ($sheet['columns'] as $column)
                        <div class="xs-card">
                            <div class="xs-head h-{{ $column['tone'] ?? 'blue' }}">{{ $column['title'] }}</div>
                            <div class="xs-lines">
                                @foreach ($column['lines'] as $line)
                                    @php $object = $line['object'] ?? null; @endphp
                                    <p style="padding-left: {{ ($line['indent'] ?? 0) * 1.1 }}rem" @class(['xs-note' => $line['note'] ?? false])>
                                        {{ $line['text'] }}
                                        @if ($object !== null && $withFindings->has($object))
                                            <button type="button" class="xs-flag" x-on:click="go(@js($object))" {!! $hover($object) !!} aria-label="{{ $t('diagram_click_hint') }}">⚠</button>
                                        @endif
                                    </p>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Destinations and fallbacks: no connector to the columns --}}
        @if (! empty($sheet['destinations']) || ! empty($sheet['fallbacks']))
            <div>
                <h4 class="xs-h">{{ $t('sheet_destinations') }}</h4>
                <div class="xs-grid dest">
                    @foreach ($sheet['destinations'] as $destination)
                        <div class="xs-card">
                            <div class="xs-head h-{{ $headerOf($destination['type'] ?? null) }}">
                                <span>{{ $destination['title'] }}</span>
                                @if ($withFindings->has($destination['object']))
                                    <button type="button" class="xs-flag" x-on:click="go(@js($destination['object']))" {!! $hover($destination['object']) !!} aria-label="{{ $t('diagram_click_hint') }}">⚠</button>
                                @endif
                            </div>
                            <div class="xs-lines">
                                @foreach ($destination['lines'] as $line)
                                    <p>{{ $line }}</p>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                    @foreach ($sheet['fallbacks'] as $fallback)
                        <div class="xs-card fallback">
                            <div class="xs-head h-fallback">{{ $fallback['title'] }}</div>
                            <div class="xs-lines">
                                @foreach ($fallback['lines'] as $line)
                                    <p style="padding-left: {{ ($line['indent'] ?? 0) * 1.1 }}rem">{{ $line['text'] }}</p>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Watch points --}}
        @if (! empty($sheet['vigilance']))
            <div class="xs-watch">
                <h4>{{ $t('sheet_vigilance') }}</h4>
                <ul>
                    @foreach ($sheet['vigilance'] as $item)
                        <li>
                            <span class="xs-dot {{ $item['category'] ?? '' }}"></span>
                            <button type="button" x-on:click="go(@js($item['object']))" {!! $hover($item['object'] ?? null) !!}>{{ $item['text'] }}</button>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <p class="xs-footer">{{ $sheet['footer'] }}</p>
    </div>

    {{-- Recommendations of the hovered object --}}
    <div x-show="tip" x-cloak role="tooltip" :style="`left: ${tipX}px; top: ${tipY}px`"
         class="absolute z-20 w-80 pointer-events-none rounded-lg shadow-lg ring-1 ring-gray-950/10 dark:ring-white/10 bg-white dark:bg-gray-900 p-3 space-y-2 text-xs">
        <template x-for="(item, i) in (tip ? tips[tip] : [])" :key="i">
            <div>
                <span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-medium ring-1" :class="item.badge" x-text="item.category"></span>
                <span class="ml-1 font-semibold text-gray-800 dark:text-gray-100" x-text="item.label"></span>
                <p class="mt-1 text-gray-600 dark:text-gray-300" x-text="item.text"></p>
            </div>
        </template>
        <p class="text-[11px] text-teal-700 dark:text-teal-400">{{ $t('diagram_click_hint') }}</p>
    </div>
</div>
