@php
    $messages = $this->messages;
    $pendingAction = $this->pendingAction;
@endphp

<div x-data="{ open: $wire.entangle('open') }" @keydown.escape.window="open = false">
    {{-- Floating trigger --}}
    <button
        type="button"
        @click="open = true"
        x-show="! open"
        x-transition
        class="fixed bottom-6 right-6 z-40 inline-flex items-center gap-2 rounded-full bg-gray-900 px-4 py-3 text-sm font-medium text-white shadow-lg shadow-black/10 transition hover:bg-gray-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-gray-900 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-200"
    >
        <x-heroicon-o-sparkles class="h-5 w-5" />
        <span>{{ __('expert-statistics::pbx.docs_assistant.button_label') }}</span>
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
            class="absolute inset-y-0 right-0 flex w-full max-w-md"
            style="display: none;"
        >
            <div class="flex h-full w-full flex-col bg-white shadow-2xl dark:bg-gray-900">
                {{-- Header --}}
                <div class="flex items-center justify-between gap-4 border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-sparkles class="h-5 w-5 text-gray-400" />
                        <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">
                            {{ __('expert-statistics::pbx.docs_assistant.panel_title') }}
                        </h2>
                    </div>
                    <button
                        type="button"
                        @click="open = false"
                        class="rounded-md p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800 dark:hover:text-gray-300"
                    >
                        <x-heroicon-o-x-mark class="h-5 w-5" />
                        <span class="sr-only">{{ __('expert-statistics::pbx.docs_assistant.close') }}</span>
                    </button>
                </div>

                {{-- Boundary disclaimer - sets expectations up front, matches the hard architectural limits in this class's docblock --}}
                <div class="border-b border-gray-200 bg-blue-50 px-4 py-2 text-xs text-blue-700 dark:border-gray-700 dark:bg-blue-950/40 dark:text-blue-300">
                    {{ $this->actionsEnabled()
                        ? __('expert-statistics::pbx.docs_assistant.disclaimer_with_actions')
                        : __('expert-statistics::pbx.docs_assistant.disclaimer') }}
                </div>

                <div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-4 py-4">
                    @if ($messages === [])
                        <div class="flex h-full flex-col items-center justify-center gap-4 text-center">
                            <x-heroicon-o-sparkles class="h-8 w-8 text-gray-300 dark:text-gray-600" />
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ __('expert-statistics::pbx.docs_assistant.welcome') }}
                            </p>
                            <div class="flex flex-wrap justify-center gap-2">
                                @foreach ($this->starterSuggestions() as $suggestion)
                                    <button
                                        type="button"
                                        @click="$wire.useSuggestion(@js($suggestion)).then(() => $wire.getResponse())"
                                        class="rounded-full border border-gray-300 px-3 py-1.5 text-xs text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800"
                                    >
                                        {{ $suggestion }}
                                    </button>
                                @endforeach
                            </div>
                            <button
                                type="button"
                                wire:click="explainCapabilities"
                                class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                            >
                                <x-heroicon-m-question-mark-circle class="h-3.5 w-3.5" />
                                {{ __('expert-statistics::pbx.docs_assistant.what_can_you_do') }}
                            </button>
                        </div>
                    @else
                        @foreach ($messages as $message)
                            @if ($message['role'] === 'user')
                                <div class="flex justify-end">
                                    <div class="max-w-[85%] rounded-2xl rounded-tr-sm bg-gray-900 px-4 py-2 text-sm text-white dark:bg-white dark:text-gray-900">
                                        {{ $message['content'] }}
                                    </div>
                                </div>
                            @else
                                <div class="flex justify-start">
                                    <div class="max-w-[85%] space-y-2">
                                        <div class="rounded-2xl rounded-tl-sm bg-gray-100 px-4 py-2 text-sm text-gray-800 dark:bg-gray-800 dark:text-gray-100">
                                            {{ $message['content'] }}
                                        </div>
                                        @if (! empty($message['suggestedSectionId']))
                                            <button
                                                type="button"
                                                wire:click="goToSuggestion('{{ $message['suggestedSectionId'] }}')"
                                                class="inline-flex items-center gap-1 rounded-full border border-gray-300 px-3 py-1 text-xs font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800"
                                            >
                                                {{ __('expert-statistics::pbx.docs_assistant.go_to_page', ['page' => $this->sectionTitle($message['suggestedSectionId'])]) }}
                                                <x-heroicon-m-arrow-right class="h-3 w-3" />
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    @endif

                    @if ($pendingAction)
                        <div class="rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 p-4 dark:border-gray-600 dark:bg-gray-800/50">
                            <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                {{ $pendingAction['type'] === 'report'
                                    ? __('expert-statistics::pbx.docs_assistant.pending_report_label')
                                    : __('expert-statistics::pbx.docs_assistant.pending_group_label') }}
                            </p>
                            <p class="text-sm text-gray-800 dark:text-gray-100">
                                {{ $pendingAction['summary'] }}
                            </p>

                            @if (! empty($pendingAction['missingFields']))
                                <p class="mt-2 text-xs text-amber-700 dark:text-amber-400">
                                    {{ __('expert-statistics::pbx.docs_assistant.pending_missing_fields', ['fields' => implode(', ', $pendingAction['missingFields'])]) }}
                                </p>
                            @endif

                            @if ($pendingAction['readyToConfirm'])
                                <div class="mt-3 flex gap-2">
                                    <button
                                        type="button"
                                        wire:click="confirmAction"
                                        wire:loading.attr="disabled"
                                        wire:target="confirmAction"
                                        class="inline-flex items-center gap-1 rounded-full bg-gray-900 px-3 py-1.5 text-xs font-medium text-white hover:bg-gray-700 disabled:opacity-50 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-200"
                                    >
                                        <x-heroicon-m-check class="h-3.5 w-3.5" />
                                        {{ __('expert-statistics::pbx.docs_assistant.confirm') }}
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="cancelAction"
                                        wire:loading.attr="disabled"
                                        wire:target="confirmAction"
                                        class="inline-flex items-center gap-1 rounded-full border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800"
                                    >
                                        <x-heroicon-m-x-mark class="h-3.5 w-3.5" />
                                        {{ __('expert-statistics::pbx.docs_assistant.cancel') }}
                                    </button>
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- Driven by the server-side $thinking flag (set by sendMessage(), cleared by
                         getResponse()) rather than wire:loading, so it reflects "user message sent,
                         reply not generated yet" across the two chained requests, not just whichever
                         one happens to be in flight. --}}
                    @if ($thinking)
                        <div class="flex justify-start">
                            <div class="flex items-center gap-1 rounded-2xl rounded-tl-sm bg-gray-100 px-4 py-3 dark:bg-gray-800">
                                <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-gray-400 [animation-delay:-0.3s]"></span>
                                <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-gray-400 [animation-delay:-0.15s]"></span>
                                <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-gray-400"></span>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- sendMessage() (fast: just appends the message) then getResponse() (the actual LLM
                     call) are chained client-side so the browser renders the user's own message before
                     the slower request even starts, instead of both appearing together at the end. --}}
                <form @submit.prevent="$wire.sendMessage().then(() => $wire.getResponse())" class="flex items-center gap-2 border-t border-gray-200 px-4 py-3 dark:border-gray-700">
                    @if ($messages !== [])
                        <button
                            type="button"
                            wire:click="clearConversation"
                            title="{{ __('expert-statistics::pbx.docs_assistant.new_conversation') }}"
                            class="shrink-0 rounded-md p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800 dark:hover:text-gray-300"
                        >
                            <x-heroicon-o-arrow-path class="h-4 w-4" />
                            <span class="sr-only">{{ __('expert-statistics::pbx.docs_assistant.new_conversation') }}</span>
                        </button>
                    @endif

                    <input
                        type="text"
                        wire:model="question"
                        placeholder="{{ __('expert-statistics::pbx.docs_assistant.input_placeholder') }}"
                        autocomplete="off"
                        :disabled="$wire.thinking"
                        class="min-w-0 flex-1 rounded-full border-gray-300 text-sm focus:border-gray-500 focus:ring-gray-500 disabled:opacity-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                    />

                    <button
                        type="submit"
                        :disabled="$wire.thinking"
                        class="shrink-0 rounded-full bg-gray-900 p-2 text-white transition hover:bg-gray-700 disabled:opacity-50 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-200"
                    >
                        <x-heroicon-m-paper-airplane class="h-4 w-4" />
                        <span class="sr-only">{{ __('expert-statistics::pbx.docs_assistant.send') }}</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
