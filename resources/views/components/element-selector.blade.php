@props([
    'urlType' => 'extension',
    'pbxElements' => [],
    'selectedElements' => [],
    'groupSelectedName' => [],
])

{{-- For caller type, always show the selector (group-based, no map fallback).
     For other types, fall back to plain DN input when no PBX elements are loaded. --}}
@if (! empty($pbxElements) || $urlType === 'caller')
{{-- Visual element selector (Alpine.js + Livewire $wire) --}}
<div
    x-data="{
        open: false,
        search: '',
        showAllChips: false,
        loadingGroup: null,
        selected: $wire.entangle('selectedElements'),
        groupSelectedNames: $wire.entangle('groupSelectedName'),
        elements: @js($pbxElements),
        get filtered() {
            const s = this.search.toLowerCase();
            if (!s) return this.elements;
            return this.elements.filter(e => e.label.toLowerCase().includes(s));
        },
        get visibleChips() {
            return this.showAllChips ? this.selected : this.selected.slice(0, 5);
        },
        get visibleGroupChips() {
            return this.showAllChips ? this.groupSelectedNames : this.groupSelectedNames.slice(0, 5);
        },
        filterDn(val) {
            if (typeof val !== 'string') return '';
            return val.split(' ')[0].replace(/^\*/, '0').replace(/[^0-9]/g, '');
        },
        isGroupSelected(label) {
            return this.groupSelectedNames.includes('*') || this.groupSelectedNames.includes(label);
        },
        isElementSelected(val) {
            return this.selected.includes(this.filterDn(val));
        }
    }"
    class="space-y-2"
>
    {{-- Trigger button --}}
    <div class="relative">
        <button
            @click="open = !open"
            type="button"
            class="inline-flex items-center gap-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 px-3.5 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 transition min-w-48"
        >
            <x-heroicon-o-magnifying-glass class="w-4 h-4 text-gray-400 shrink-0" />
            <span
                x-text="@if ($urlType === 'caller') groupSelectedNames.includes('*')
                    ? '{{ __('expert-statistics::pbx.expert_statistics.selector_all_groups') }}'
                    : (groupSelectedNames.length > 0
                        ? groupSelectedNames.length + ' {{ __('expert-statistics::pbx.expert_statistics.selector_selected') }}'
                        : '{{ __('expert-statistics::pbx.expert_statistics.selector_placeholder_'.$urlType) }}')
                @else selected.length > 0
                    ? selected.length + ' {{ __('expert-statistics::pbx.expert_statistics.selector_selected') }}'
                    : '{{ __('expert-statistics::pbx.expert_statistics.selector_placeholder_'.$urlType) }}'
                @endif"
                class="flex-1 text-left truncate"
            ></span>
            <x-heroicon-m-chevron-down class="w-3 h-3 text-gray-400 shrink-0" />
        </button>

        {{-- Dropdown panel --}}
        <div
            x-show="open"
            x-transition.opacity
            @click.away="open = false"
            class="absolute left-0 top-full mt-2 z-50 w-80 bg-white dark:bg-gray-900 rounded-2xl shadow-2xl ring-1 ring-gray-950/10 dark:ring-white/10 p-4 space-y-3"
        >
            {{-- Search input --}}
            <input
                type="text"
                x-model="search"
                placeholder="{{ __('expert-statistics::pbx.expert_statistics.selector_search') }}"
                class="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-sm px-3 py-2 text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            />

            {{-- Action buttons --}}
            <div class="flex gap-2">
                <button
                    @click="$wire.selectAllPbxElements()"
                    type="button"
                    class="flex-1 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 px-3 py-1.5 text-xs font-medium text-gray-600 dark:text-gray-300 transition"
                >
                    {{ __('expert-statistics::pbx.expert_statistics.selector_select_all') }}
                </button>
                <button
                    @click="$wire.clearPbxElements()"
                    type="button"
                    :disabled="selected.length === 0"
                    :class="selected.length === 0
                        ? 'opacity-40 cursor-not-allowed'
                        : 'hover:bg-gray-100 dark:hover:bg-gray-700'"
                    class="flex-1 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3 py-1.5 text-xs font-medium text-gray-600 dark:text-gray-300 transition"
                >
                    {{ __('expert-statistics::pbx.expert_statistics.selector_clear') }}
                </button>
            </div>

            {{-- Element list --}}
            <div class="max-h-56 overflow-y-auto -mx-1 px-1 space-y-0.5">
                <template x-for="elem in filtered" :key="(elem.isConstructor ? 'g' : 'e') + '_' + elem.label">
                    <div>
                        {{-- Group header row --}}
                        <template x-if="elem.isConstructor">
                            <div
                                @click="if (loadingGroup === null) { loadingGroup = elem.label; await $wire.addElement(elem); loadingGroup = null; }"
                                :class="{
                                    'bg-primary-50 dark:bg-primary-950/30 text-primary-700 dark:text-primary-300': isGroupSelected(elem.label),
                                    'hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-800 dark:text-gray-200': !isGroupSelected(elem.label),
                                    'cursor-wait opacity-60': loadingGroup === elem.label,
                                    'cursor-pointer': loadingGroup === null,
                                    'cursor-not-allowed opacity-40': loadingGroup !== null && loadingGroup !== elem.label,
                                }"
                                class="flex items-center gap-2 px-2 py-1.5 rounded-lg transition-opacity"
                            >
                                {{-- Group icon (hidden while this group is loading) --}}
                                <svg
                                    x-show="loadingGroup !== elem.label"
                                    class="w-3.5 h-3.5 shrink-0 text-gray-400 dark:text-gray-500"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>

                                {{-- Spinner (shown while this group is loading) --}}
                                <svg
                                    x-show="loadingGroup === elem.label"
                                    class="w-3.5 h-3.5 shrink-0 text-primary-500 animate-spin"
                                    fill="none" viewBox="0 0 24 24"
                                >
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>

                                <span x-text="elem.label" class="text-sm font-semibold flex-1 truncate"></span>
                                <span x-text="'(' + (Array.isArray(elem.value) ? elem.value.length : 0) + ')'" class="text-xs text-gray-400 dark:text-gray-500 shrink-0"></span>

                                {{-- Checkmark (shown when selected and not currently loading) --}}
                                <svg
                                    x-show="isGroupSelected(elem.label) && loadingGroup !== elem.label"
                                    class="w-4 h-4 text-primary-500 shrink-0"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                        </template>

                        {{-- Individual element row --}}
                        <template x-if="!elem.isConstructor">
                            <label class="flex items-center gap-2.5 px-2 py-1.5 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer">
                                <input
                                    type="checkbox"
                                    :checked="isElementSelected(elem.value)"
                                    @change="$wire.addElement(elem)"
                                    class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500"
                                />
                                <span
                                    x-text="elem.label"
                                    class="text-sm text-gray-700 dark:text-gray-300 truncate"
                                ></span>
                            </label>
                        </template>
                    </div>
                </template>

                <p
                    x-show="filtered.length === 0"
                    class="px-2 py-3 text-sm text-center text-gray-400 dark:text-gray-500"
                >
                    @if ($urlType === 'caller')
                        {{ __('expert-statistics::pbx.expert_statistics.selector_no_caller_groups') }}
                    @else
                        {{ __('expert-statistics::pbx.expert_statistics.selector_no_results') }}
                    @endif
                </p>
            </div>

            {{-- Apply button --}}
            <button
                @click="$wire.applyPbxElementSelection(); open = false"
                type="button"
                class="w-full rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium py-2 transition"
            >
                {{ __('expert-statistics::pbx.expert_statistics.selector_apply') }}
            </button>
        </div>
    </div>

    {{-- Selected chips --}}
    @if ($urlType === 'caller')
    {{-- Caller type: one chip per selected group name --}}
    <div class="flex flex-wrap gap-1.5" x-show="groupSelectedNames.length > 0">
        <template x-for="groupName in visibleGroupChips" :key="groupName">
            <span class="inline-flex items-center gap-1 rounded-full bg-primary-100 dark:bg-primary-950/50 text-primary-800 dark:text-primary-200 text-xs font-medium pl-2.5 pr-1.5 py-1 ring-1 ring-primary-200 dark:ring-primary-800 max-w-48">
                <span x-text="groupName === '*' ? '{{ __('expert-statistics::pbx.expert_statistics.selector_all_groups') }}' : groupName" class="truncate"></span>
                <button
                    @click="$wire.removePbxGroup(groupName)"
                    type="button"
                    class="rounded-full hover:bg-primary-200 dark:hover:bg-primary-800 p-0.5 transition"
                >
                    <x-heroicon-m-x-mark class="w-3 h-3" />
                </button>
            </span>
        </template>

        {{-- Show more / show less toggle --}}
        <template x-if="groupSelectedNames.length > 5">
            <button
                @click="showAllChips = !showAllChips"
                type="button"
                class="inline-flex items-center gap-1 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 text-xs font-medium px-2.5 py-1 ring-1 ring-gray-200 dark:ring-gray-700 hover:bg-gray-200 dark:hover:bg-gray-700 transition"
            >
                <span x-text="showAllChips ? '{{ __('expert-statistics::pbx.expert_statistics.selector_show_less') }}' : '+' + (groupSelectedNames.length - 5) + ' {{ __('expert-statistics::pbx.expert_statistics.selector_show_more') }}'"></span>
            </button>
        </template>
    </div>
    @else
    {{-- Default: one chip per selected DN --}}
    <div class="flex flex-wrap gap-1.5" x-show="selected.length > 0">
        <template x-for="dn in visibleChips" :key="dn">
            <span class="inline-flex items-center gap-1 rounded-full bg-primary-100 dark:bg-primary-950/50 text-primary-800 dark:text-primary-200 text-xs font-medium pl-2.5 pr-1.5 py-1 ring-1 ring-primary-200 dark:ring-primary-800 max-w-48">
                <span
                    x-text="(elements.find(e => !e.isConstructor && filterDn(e.value) === dn) || { label: dn }).label"
                    class="truncate"
                ></span>
                <button
                    @click="$wire.removePbxElement(dn)"
                    type="button"
                    class="rounded-full hover:bg-primary-200 dark:hover:bg-primary-800 p-0.5 transition"
                >
                    <x-heroicon-m-x-mark class="w-3 h-3" />
                </button>
            </span>
        </template>

        {{-- Show more / show less toggle --}}
        <template x-if="selected.length > 5">
            <button
                @click="showAllChips = !showAllChips"
                type="button"
                class="inline-flex items-center gap-1 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 text-xs font-medium px-2.5 py-1 ring-1 ring-gray-200 dark:ring-gray-700 hover:bg-gray-200 dark:hover:bg-gray-700 transition"
            >
                <span x-text="showAllChips ? '{{ __('expert-statistics::pbx.expert_statistics.selector_show_less') }}' : '+' + (selected.length - 5) + ' {{ __('expert-statistics::pbx.expert_statistics.selector_show_more') }}'"></span>
            </button>
        </template>
    </div>
    @endif
</div>

@else
{{-- Fallback: plain text DN input when no PBX elements loaded --}}
<div class="flex gap-3 items-center">
    <div class="flex-1">
        <input
            type="text"
            wire:model.defer="dn"
            placeholder="{{ __('expert-statistics::pbx.expert_statistics.filter_dn_default') }}"
            class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 focus:ring-primary-500 focus:border-primary-500"
        />
    </div>
    <button
        wire:click="applyFilters"
        type="button"
        class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 rounded-xl shadow-sm transition"
    >
        <x-heroicon-m-magnifying-glass class="w-4 h-4" />
        {{ __('expert-statistics::pbx.expert_statistics.filter_search') }}
    </button>
</div>
@endif
