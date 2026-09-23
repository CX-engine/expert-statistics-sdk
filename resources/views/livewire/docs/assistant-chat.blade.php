@php
    $messages = $this->messages;
@endphp

<div class="flex h-full min-h-0 flex-col">
    {{-- Boundary disclaimer - sets expectations up front, matches the hard architectural limits in DocsAssistantChat's docblock --}}
    <div class="border-b border-gray-200 bg-blue-50 px-4 py-2 text-xs text-blue-700 dark:border-gray-700 dark:bg-blue-950/40 dark:text-blue-300">
        {{ __('expert-statistics::pbx.docs_assistant.disclaimer') }}
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
                            wire:click="useSuggestion(@js($suggestion))"
                            class="rounded-full border border-gray-300 px-3 py-1.5 text-xs text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800"
                        >
                            {{ $suggestion }}
                        </button>
                    @endforeach
                </div>
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

        <div wire:loading wire:target="ask,useSuggestion" class="flex justify-start">
            <div class="flex items-center gap-1 rounded-2xl rounded-tl-sm bg-gray-100 px-4 py-3 dark:bg-gray-800">
                <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-gray-400 [animation-delay:-0.3s]"></span>
                <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-gray-400 [animation-delay:-0.15s]"></span>
                <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-gray-400"></span>
            </div>
        </div>
    </div>

    <form wire:submit="ask" class="flex items-center gap-2 border-t border-gray-200 px-4 py-3 dark:border-gray-700">
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
            class="min-w-0 flex-1 rounded-full border-gray-300 text-sm focus:border-gray-500 focus:ring-gray-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
        />

        <button
            type="submit"
            wire:loading.attr="disabled"
            wire:target="ask"
            class="shrink-0 rounded-full bg-gray-900 p-2 text-white transition hover:bg-gray-700 disabled:opacity-50 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-200"
        >
            <x-heroicon-m-paper-airplane class="h-4 w-4" />
            <span class="sr-only">{{ __('expert-statistics::pbx.docs_assistant.send') }}</span>
        </button>
    </form>
</div>
