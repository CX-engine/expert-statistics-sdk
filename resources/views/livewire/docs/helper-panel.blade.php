@php
    $catalog = $this->catalog;
    $activeSection = $this->activeSection;
@endphp

<div
    x-data="{
        open: $wire.entangle('open'),
        sectionIdsByFile: @js($this->sectionIdsByFile),
        navigateDocLink(event) {
            const anchor = event.target.closest('a');
            if (! anchor) { return; }

            const href = anchor.getAttribute('href') || '';
            const [file, hash] = href.split('#');
            const sectionId = this.sectionIdsByFile[file];

            if (! sectionId) { return; }

            event.preventDefault();

            $wire.select(sectionId).then(() => {
                requestAnimationFrame(() => {
                    if (hash) {
                        this.$refs.content?.querySelector('#' + CSS.escape(hash))?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    } else {
                        this.$refs.content?.scrollTo({ top: 0 });
                    }
                });
            });
        },
    }"
    @keydown.escape.window="open = false"
>
    {{-- Floating trigger --}}
    <button
        type="button"
        @click="open = true"
        x-show="! open"
        x-transition
        class="fixed bottom-6 left-6 z-40 inline-flex items-center gap-2 rounded-full bg-gray-900 px-4 py-3 text-sm font-medium text-white shadow-lg shadow-black/10 transition hover:bg-gray-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-gray-900 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-200"
    >
        <x-heroicon-o-book-open class="h-5 w-5" />
        <span>{{ __('expert-statistics::pbx.docs.button_label') }}</span>
    </button>

    {{-- Overlay + slide-over panel --}}
    <div class="fixed inset-0 z-50" :class="open ? 'pointer-events-auto' : 'pointer-events-none'">
        <div
            x-show="open"
            x-transition.opacity
            @click="open = false"
            class="absolute inset-0 bg-gray-900/50"
            style="display: none;"
        ></div>

        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
            class="absolute inset-y-0 right-0 flex w-full max-w-3xl"
            style="display: none;"
        >
            <div class="flex h-full w-full flex-col bg-white shadow-2xl dark:bg-gray-900">
                {{-- Header --}}
                <div class="flex items-center justify-between gap-4 border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-book-open class="h-5 w-5 text-gray-400" />
                        <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">
                            {{ __('expert-statistics::pbx.docs.panel_title') }}
                        </h2>
                    </div>
                    <div class="flex items-center gap-3">
                        @if ($this->assistantEnabled)
                            <div class="flex rounded-full bg-gray-100 p-0.5 text-sm dark:bg-gray-800">
                                <button
                                    type="button"
                                    wire:click="showBrowse"
                                    @class([
                                        'rounded-full px-3 py-1 transition',
                                        'bg-white text-gray-900 shadow dark:bg-gray-700 dark:text-white' => $mode === 'browse',
                                        'text-gray-500 dark:text-gray-400' => $mode !== 'browse',
                                    ])
                                >
                                    {{ __('expert-statistics::pbx.docs.tab_browse') }}
                                </button>
                                <button
                                    type="button"
                                    wire:click="showAsk"
                                    @class([
                                        'rounded-full px-3 py-1 transition',
                                        'bg-white text-gray-900 shadow dark:bg-gray-700 dark:text-white' => $mode === 'ask',
                                        'text-gray-500 dark:text-gray-400' => $mode !== 'ask',
                                    ])
                                >
                                    {{ __('expert-statistics::pbx.docs.tab_ask') }}
                                </button>
                            </div>
                        @endif
                        <button
                            type="button"
                            @click="open = false"
                            class="rounded-md p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800 dark:hover:text-gray-300"
                        >
                            <x-heroicon-o-x-mark class="h-5 w-5" />
                            <span class="sr-only">{{ __('expert-statistics::pbx.docs.close') }}</span>
                        </button>
                    </div>
                </div>

                @if ($mode === 'ask' && $this->assistantEnabled)
                    <div class="min-h-0 flex-1" wire:key="docs-assistant-chat-wrapper">
                        <livewire:expert-statistics.docs.assistant-chat wire:key="docs-assistant-chat" />
                    </div>
                @else
                <div class="flex min-h-0 flex-1">
                    {{-- Table of contents --}}
                    <nav class="hidden w-64 shrink-0 overflow-y-auto border-r border-gray-200 p-4 sm:block dark:border-gray-700">
                        <div class="relative mb-4">
                            <x-heroicon-o-magnifying-glass class="pointer-events-none absolute left-2.5 top-2.5 h-4 w-4 text-gray-400" />
                            <input
                                type="search"
                                wire:model.live.debounce.250ms="search"
                                placeholder="{{ __('expert-statistics::pbx.docs.search_placeholder') }}"
                                class="w-full rounded-md border-gray-300 pl-8 text-sm focus:border-gray-500 focus:ring-gray-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                            />
                        </div>

                        @if ($search !== '')
                            <p class="mb-2 px-1 text-xs font-medium uppercase tracking-wide text-gray-400">
                                {{ __('expert-statistics::pbx.docs.search_results') }}
                            </p>
                            <ul class="space-y-0.5">
                                @forelse ($this->searchResults as $result)
                                    <li>
                                        <button
                                            type="button"
                                            wire:click="select('{{ $result['id'] }}')"
                                            class="block w-full rounded-md px-2 py-1.5 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800"
                                        >
                                            {{ $result['title'] }}
                                            <span class="block text-xs text-gray-400">{{ $result['group'] }}</span>
                                        </button>
                                    </li>
                                @empty
                                    <li class="px-2 py-1.5 text-sm text-gray-400">{{ __('expert-statistics::pbx.docs.no_results') }}</li>
                                @endforelse
                            </ul>
                        @else
                            @foreach ($catalog as $group)
                                <p class="mb-1 mt-4 px-1 text-xs font-medium uppercase tracking-wide text-gray-400 first:mt-0">
                                    {{ $this->groupTitle($group['group']) }}
                                </p>
                                <ul class="mb-2 space-y-0.5">
                                    @foreach ($group['sections'] as $section)
                                        <li>
                                            <button
                                                type="button"
                                                wire:click="select('{{ $section['id'] }}')"
                                                @class([
                                                    'block w-full rounded-md px-2 py-1.5 text-left text-sm transition',
                                                    'bg-gray-900 text-white dark:bg-white dark:text-gray-900' => $activeSection && $activeSection['id'] === $section['id'],
                                                    'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800' => ! $activeSection || $activeSection['id'] !== $section['id'],
                                                ])
                                            >
                                                {{ $this->sectionTitle($section['id']) }}
                                            </button>
                                        </li>
                                    @endforeach
                                </ul>
                            @endforeach
                        @endif
                    </nav>

                    {{-- Content --}}
                    <div
                        x-ref="content"
                        @click="navigateDocLink($event)"
                        class="min-w-0 flex-1 overflow-y-auto px-6 py-6"
                        wire:loading.class="opacity-50"
                        wire:target="select"
                    >
                        {{-- Mobile section picker (TOC sidebar is hidden below sm:) --}}
                        <div class="mb-4 sm:hidden">
                            <select wire:model.live="activeSectionId" class="w-full rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                @foreach ($catalog as $group)
                                    <optgroup label="{{ $this->groupTitle($group['group']) }}">
                                        @foreach ($group['sections'] as $section)
                                            <option value="{{ $section['id'] }}">{{ $this->sectionTitle($section['id']) }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>

                        @if ($activeSection)
                            <article class="prose prose-sm max-w-none dark:prose-invert prose-a:text-blue-600 dark:prose-a:text-blue-400">
                                {!! $this->activeContent !!}
                            </article>
                        @endif
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
