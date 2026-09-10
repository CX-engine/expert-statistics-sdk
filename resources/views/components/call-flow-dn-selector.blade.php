@props([
    'elements' => [],
    'selectedProp',
    'placeholder' => '',
    'wireAdd',
    'wireRemove',
])

{{--
    Multi-select dropdown for one Call Flow Analysis filter dimension
    (origin/destination/DID/caller). Unlike <x-expert-statistics::element-selector>
    (built around a single shared dn/selectedElements/pbxElements property
    set), this component is instantiated up to four times per page, each
    bound to its own Livewire array property (via $selectedProp, entangled)
    and its own pair of add/remove methods - mirrors the "call-flow-dn-selector"
    component referenced by bluerocktelclients' call-analysis.blade.php.
--}}
<div
    x-data="{
        open: false,
        search: '',
        selected: $wire.entangle('{{ $selectedProp }}'),
        elements: @js($elements),
        get filtered() {
            const s = this.search.toLowerCase();
            if (!s) return this.elements;
            return this.elements.filter(e => e.label.toLowerCase().includes(s));
        },
        filterDn(val) {
            if (typeof val !== 'string') return '';
            return val.split(' ')[0].replace(/^\*/, '0').replace(/[^0-9]/g, '');
        },
        isSelected(elem) {
            if (Array.isArray(elem.value)) {
                return elem.value.length > 0 && elem.value.every(v => this.selected.includes(this.filterDn(String(v))));
            }
            return this.selected.includes(this.filterDn(String(elem.value)));
        }
    }"
    class="relative space-y-1.5"
>
    <button
        @click="open = !open"
        type="button"
        class="w-full flex items-center justify-between gap-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 px-3 py-2 text-sm text-gray-700 dark:text-gray-300 transition"
    >
        <span
            class="truncate"
            x-text="selected.length > 0 ? selected.length + ' {{ __('expert-statistics::pbx.expert_statistics.selector_selected') }}' : '{{ $placeholder }}'"
        ></span>
        <x-heroicon-m-chevron-down class="w-3.5 h-3.5 text-gray-400 shrink-0" />
    </button>

    <div
        x-show="open"
        x-transition.opacity
        @click.away="open = false"
        x-cloak
        class="absolute left-0 top-full mt-1.5 z-50 w-full min-w-64 bg-white dark:bg-gray-900 rounded-xl shadow-xl ring-1 ring-gray-950/10 dark:ring-white/10 p-3 space-y-2"
    >
        <input
            type="text"
            x-model="search"
            placeholder="{{ __('expert-statistics::pbx.expert_statistics.selector_search') }}"
            class="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-xs px-2.5 py-1.5 text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
        />

        <div class="max-h-48 overflow-y-auto -mx-1 px-1 space-y-0.5">
            <template x-for="elem in filtered" :key="(elem.isConstructor ? 'g' : 'e') + '_' + elem.label">
                <label
                    class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer"
                    :class="elem.isConstructor ? 'bg-gray-50/60 dark:bg-gray-800/40' : ''"
                >
                    <input
                        type="checkbox"
                        :checked="isSelected(elem)"
                        @change="$wire.{{ $wireAdd }}(elem)"
                        class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500"
                    />
                    <span
                        x-text="elem.label"
                        :class="elem.isConstructor
                            ? 'text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400'
                            : 'text-sm text-gray-700 dark:text-gray-300'"
                        class="truncate"
                    ></span>
                </label>
            </template>

            <p x-show="filtered.length === 0" class="px-2 py-3 text-xs text-center text-gray-400 dark:text-gray-500">
                {{ __('expert-statistics::pbx.expert_statistics.selector_no_results') }}
            </p>
        </div>
    </div>

    {{-- Selected chips --}}
    <div class="flex flex-wrap gap-1.5" x-show="selected.length > 0">
        <template x-for="dn in selected" :key="dn">
            <span class="inline-flex items-center gap-1 rounded-full bg-primary-100 dark:bg-primary-950/50 text-primary-800 dark:text-primary-200 text-xs font-medium pl-2.5 pr-1.5 py-1 ring-1 ring-primary-200 dark:ring-primary-800 max-w-40">
                <span
                    x-text="(elements.find(e => !e.isConstructor && filterDn(String(e.value)) === dn) || { label: dn }).label"
                    class="truncate"
                ></span>
                <button
                    @click="$wire.{{ $wireRemove }}(dn)"
                    type="button"
                    class="rounded-full hover:bg-primary-200 dark:hover:bg-primary-800 p-0.5 transition"
                >
                    <x-heroicon-m-x-mark class="w-3 h-3" />
                </button>
            </span>
        </template>
    </div>
</div>
