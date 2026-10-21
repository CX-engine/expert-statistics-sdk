<div class="space-y-4">
    @if ($selectedDid !== null)
        {{-- ── One DID: resolved routing ─────────────────────────────────────── --}}
        @php
            $route = $routeDetail['route'] ?? [];
            $nodes = collect($route['nodes'] ?? []);
            $labels = $nodes->pluck('label', 'id');
        @endphp
        <div class="flex items-center justify-between">
            <button wire:click="closeRoute" class="inline-flex items-center gap-1 text-sm text-teal-600 hover:underline">
                <x-heroicon-o-arrow-left class="w-4 h-4" /> {{ $t('back_to_dids') }}
            </button>
            @if (isset($routeDetail['calls']))
                <span class="text-xs text-gray-500">{{ $t('calls_count', ['count' => number_format((int) $routeDetail['calls'], 0, ',', ' ')]) }}</span>
            @endif
        </div>

        <div wire:loading.flex wire:target="showRoute" class="justify-center py-10 text-sm text-gray-400">{{ $t('loading') }}</div>

        <div wire:loading.remove wire:target="showRoute" class="space-y-2">
            <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200">
                {{ ($route['is_default'] ?? false) ? $t('default_route', ['trunk' => $route['trunk'] ?? '']) : $t('did_title', ['did' => $route['did'] ?? $selectedDid]) }}
                @if (! empty($route['rule_name'])) <span class="text-sm font-normal text-gray-500">– {{ $route['rule_name'] }}</span> @endif
            </h3>

            @foreach ($nodes as $node)
                <div wire:key="node-{{ $node['id'] }}" class="bg-white dark:bg-gray-800 rounded-lg ring-1 ring-gray-950/5 dark:ring-white/10 px-4 py-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-[11px] uppercase tracking-wide text-gray-400">{{ $t('node_types.'.($node['type'] ?? 'unknown')) !== 'expert-statistics::pbx.config_audit.node_types.'.($node['type'] ?? 'unknown') ? $t('node_types.'.$node['type']) : ($node['type'] ?? '') }}</span>
                        <span class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $node['label'] ?? $node['id'] }}</span>
                    </div>
                    @php $details = collect($node['details'] ?? [])->reject(fn ($v) => is_array($v) && empty($v)); @endphp
                    @if ($details->isNotEmpty())
                        <div class="mt-1.5 flex flex-wrap gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                            @foreach ($details as $name => $value)
                                @if ($name === 'members' && is_array($value))
                                    <span class="rounded bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5">{{ $t('agents_count', ['count' => count($value)]) }}:
                                        {{ collect($value)->map(fn ($m) => is_array($m) ? trim(($m['dn'] ?? '').' '.($m['name'] ?? '')).(isset($m['status']) ? ' ('.$m['status'].')' : '') : $m)->take(12)->implode(', ') }}{{ count($value) > 12 ? ', …' : '' }}
                                    </span>
                                @elseif ($name === 'menus' && is_array($value))
                                    @foreach ($value as $menu)
                                        <span class="rounded bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5">{{ $menu['name'] ?? '' }}: {{ $t('keys') }} {{ implode(', ', $menu['options'] ?? []) }}</span>
                                    @endforeach
                                @else
                                    <span class="rounded bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5">{{ str_replace('_', ' ', $name) }}: {{ is_bool($value) ? ($value ? '✓' : '✗') : (is_array($value) ? implode(', ', array_map(fn ($v) => is_scalar($v) ? $v : json_encode($v), $value)) : $value) }}</span>
                                @endif
                            @endforeach
                        </div>
                    @endif
                    @if (! empty($node['next']))
                        <ul class="mt-2 space-y-1 text-sm">
                            @foreach ($node['next'] as $edge)
                                <li class="flex flex-wrap items-baseline gap-1.5 text-gray-600 dark:text-gray-300">
                                    <span class="text-xs text-gray-400">{{ $edge['condition'] ?? '' }}</span>
                                    <x-heroicon-o-arrow-long-right class="w-4 h-4 text-gray-400 self-center" />
                                    <span class="font-medium">{{ $labels[$edge['to'] ?? ''] ?? ($edge['to'] ?? '') }}</span>
                                    @if (! empty($edge['via_own_did']))
                                        <span class="text-[11px] rounded bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400 px-1.5">{{ $t('via_own_did') }}</span>
                                    @endif
                                    @if (! empty($edge['department_change']))
                                        <span class="text-[11px] rounded bg-sky-50 text-sky-700 dark:bg-sky-950/30 dark:text-sky-400 px-1.5">{{ $t('department_change', ['from' => $edge['department_change']['from'] ?? '', 'to' => $edge['department_change']['to'] ?? '']) }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    @if (! empty($routeDetail['observed'][$node['id']]['destinations']))
                        @php $observed = $routeDetail['observed'][$node['id']]; @endphp
                        <div class="mt-3 rounded-md bg-teal-50/60 dark:bg-teal-950/20 ring-1 ring-teal-600/10 px-3 py-2" title="{{ $t('observed_help') }}">
                            <p class="text-xs font-medium text-teal-800 dark:text-teal-300">
                                {{ $t('observed_title') }}
                                <span class="font-normal text-teal-700/80 dark:text-teal-400/80">· {{ $t('observed_summary', ['calls' => number_format((int) $observed['calls'], 0, ',', ' '), 'share' => isset($observed['observed_share']) ? round($observed['observed_share'] * 100).' %' : '—']) }}</span>
                            </p>
                            <ul class="mt-1 space-y-0.5 text-sm">
                                @foreach ($observed['destinations'] as $destination)
                                    <li class="flex flex-wrap items-baseline gap-1.5 text-gray-600 dark:text-gray-300">
                                        <x-heroicon-o-arrow-long-right class="w-4 h-4 text-gray-400 self-center" />
                                        <span class="font-medium">{{ $destination['label'] }}</span>
                                        <span class="text-xs text-gray-500">{{ $t('calls_count', ['count' => number_format((int) $destination['calls'], 0, ',', ' ')]) }}</span>
                                        @unless ($destination['in_script'])
                                            <span class="text-[11px] rounded bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300 px-1.5">{{ $t('observed_not_in_script') }}</span>
                                        @endunless
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            @endforeach

            @if (! empty($routeDetail['findings']))
                <h4 class="pt-3 text-sm font-semibold text-gray-700 dark:text-gray-300">{{ $t('findings_on_route') }}</h4>
                @foreach ($routeDetail['findings'] as $finding)
                    @include('expert-statistics::livewire.config-audit.finding-card', ['finding' => $finding, 'key' => 'route-finding-'.$loop->index])
                @endforeach
            @endif
        </div>
    @else
        {{-- ── DID list ───────────────────────────────────────────────────────── --}}
        <input type="search" wire:model.live.debounce.400ms="routeSearch" placeholder="{{ $t('search_dids') }}"
               class="w-full sm:w-80 rounded-lg border-gray-200 dark:border-gray-700 dark:bg-gray-800 text-sm py-1.5" />

        <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-lg shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900/40 text-xs text-gray-500 dark:text-gray-400 text-left">
                    <tr>
                        <th class="px-4 py-2 font-medium">{{ $t('col_did') }}</th>
                        <th class="px-4 py-2 font-medium">{{ $t('col_first_destination') }}</th>
                        <th class="px-4 py-2 font-medium text-right">{{ $t('col_calls') }}</th>
                        <th class="px-4 py-2 font-medium text-right">{{ $t('col_findings') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($routes['data'] ?? [] as $row)
                        <tr wire:key="did-{{ $row['did'] }}" wire:click="showRoute('{{ $row['did'] }}')" class="cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/40">
                            <td class="px-4 py-2">
                                <span class="font-medium text-gray-800 dark:text-gray-200">{{ ($row['is_default'] ?? false) ? $t('default_route', ['trunk' => $row['trunk'] ?? '']) : $row['did'] }}</span>
                                @if (! empty($row['rule_name'])) <span class="block text-xs text-gray-400">{{ $row['rule_name'] }}</span> @endif
                                @if (($row['is_default'] ?? false) && ! empty($row['covered_dids'])) <span class="block text-xs text-gray-400">{{ $t('covered_dids', ['count' => $row['covered_dids']]) }}</span> @endif
                            </td>
                            <td class="px-4 py-2 text-gray-600 dark:text-gray-300">{{ $row['first_destination']['label'] ?? '—' }}</td>
                            <td class="px-4 py-2 text-right text-gray-600 dark:text-gray-300">{{ isset($row['calls']) ? number_format((int) $row['calls'], 0, ',', ' ') : '—' }}</td>
                            <td class="px-4 py-2 text-right">
                                @if (($row['findings'] ?? 0) > 0)
                                    <span class="inline-flex rounded-full bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400 px-2 py-0.5 text-xs font-medium">{{ $row['findings'] }}</span>
                                @else
                                    <span class="text-xs text-gray-400">0</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-gray-500">{{ $t('no_dids') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('expert-statistics::livewire.config-audit.pagination', ['meta' => $routes['meta'] ?? [], 'list' => 'routes'])
    @endif
</div>
