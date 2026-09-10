<div class="flex gap-4 h-[calc(100vh-12rem)] min-h-[500px]">

    {{-- ── Sidebar — conversation history ──────────────────────────────── --}}
    <div class="w-64 flex-shrink-0 flex flex-col bg-white dark:bg-gray-800 rounded-2xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden">

        {{-- Sidebar header --}}
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between gap-2">
            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                {{ __('expert-statistics::pbx.expert_statistics.ai_chat_history_title') }}
            </span>
            <div class="flex items-center gap-1">
                @if (! empty($conversations))
                    <button
                        wire:click="clearHistory"
                        wire:confirm="{{ __('expert-statistics::pbx.expert_statistics.ai_chat_clear_history_confirm') }}"
                        class="p-1 rounded-lg text-gray-400 dark:text-gray-500 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-950/30 transition-colors"
                        title="{{ __('expert-statistics::pbx.expert_statistics.ai_chat_clear_history') }}"
                    >
                        <x-heroicon-o-trash class="w-4 h-4" />
                    </button>
                @endif
                <button
                    wire:click="newConversation"
                    class="p-1 rounded-lg text-primary-600 dark:text-primary-400 hover:bg-primary-50 dark:hover:bg-primary-950/30 transition-colors"
                    title="{{ __('expert-statistics::pbx.expert_statistics.ai_chat_new_conversation') }}"
                >
                    <x-heroicon-o-plus class="w-4 h-4" />
                </button>
            </div>
        </div>

        {{-- Conversation list --}}
        <div wire:loading.class="opacity-50 pointer-events-none" wire:target="selectConversation" class="flex-1 overflow-y-auto py-1">
            @forelse ($conversations as $conv)
                @php
                    $convId = (string) ($conv['uuid'] ?? $conv['id'] ?? '');
                    $convTitle = $conv['title'] ?? null;
                    $msgCount = $conv['messages_count'] ?? null;
                    $timestamp = $conv['updated_at'] ?? $conv['created_at'] ?? null;
                    $isSelected = $conversationUuid === $convId;
                @endphp
                <button
                    wire:click="selectConversation('{{ $convId }}')"
                    wire:key="ai-chat-conv-{{ $convId }}"
                    @class([
                        'w-full text-left px-4 py-3 border-b border-gray-100 dark:border-gray-700/50 transition-colors',
                        'bg-primary-50 dark:bg-primary-950/20 border-l-2 border-l-primary-400' => $isSelected,
                        'hover:bg-gray-50 dark:hover:bg-gray-700/50' => ! $isSelected,
                    ])
                >
                    <p class="text-xs font-medium text-gray-700 dark:text-gray-300 truncate leading-snug">
                        {{ $convTitle ?? __('expert-statistics::pbx.expert_statistics.ai_chat_untitled_conversation') }}
                    </p>
                    <div class="flex items-center gap-2 mt-1">
                        @if ($timestamp)
                            <span class="text-[11px] text-gray-400 dark:text-gray-500">
                                {{ \Carbon\Carbon::parse($timestamp)->diffForHumans() }}
                            </span>
                        @endif
                        @if ($msgCount)
                            <span class="text-[10px] font-semibold text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-950/30 border border-primary-100 dark:border-primary-800 px-1.5 py-0.5 rounded-full leading-none">
                                {{ $msgCount }}
                            </span>
                        @endif
                    </div>
                </button>
            @empty
                <div class="px-4 py-8 text-center">
                    <x-heroicon-o-chat-bubble-left class="w-7 h-7 mx-auto text-gray-200 dark:text-gray-700 mb-2" />
                    <p class="text-xs text-gray-400 dark:text-gray-500">
                        {{ __('expert-statistics::pbx.expert_statistics.ai_chat_no_history') }}
                    </p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- ── Main chat panel ─────────────────────────────────────────────── --}}
    <div class="relative flex-1 flex flex-col bg-white dark:bg-gray-800 rounded-2xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden min-w-0">

        {{-- Chat header --}}
        <div class="px-5 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center gap-2">
            <x-heroicon-o-sparkles class="w-5 h-5 text-primary-500 flex-shrink-0" />
            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                {{ __('expert-statistics::pbx.expert_statistics.ai_chat_title') }}
            </span>
            <span class="text-xs text-gray-400 dark:text-gray-500 ml-auto">
                {{ __('expert-statistics::pbx.expert_statistics.ai_chat_powered_by') }}
            </span>
        </div>

        {{-- Error --}}
        @if ($errorMessage)
            <div class="mx-5 mt-3 rounded-xl bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-900 px-4 py-3 text-sm text-red-700 dark:text-red-400">
                {{ $errorMessage }}
            </div>
        @endif

        {{-- Messages area --}}
        <div class="relative flex-1 overflow-y-auto px-5 py-4 space-y-4" id="ai-chat-messages">

            {{-- Overlay while fetching a conversation from history --}}
            <div wire:loading.flex wire:target="selectConversation" class="absolute inset-0 z-10 items-center justify-center bg-white/75 dark:bg-gray-800/75 rounded-b-2xl">
                <div class="flex items-center gap-1.5">
                    <span class="w-2 h-2 bg-primary-400 rounded-full animate-bounce [animation-delay:0ms]"></span>
                    <span class="w-2 h-2 bg-primary-400 rounded-full animate-bounce [animation-delay:150ms]"></span>
                    <span class="w-2 h-2 bg-primary-400 rounded-full animate-bounce [animation-delay:300ms]"></span>
                </div>
            </div>

            @if (empty($messages))
                {{-- Welcome state --}}
                <div class="flex flex-col items-center justify-center h-full text-center py-12">
                    <div class="p-4 bg-primary-50 dark:bg-primary-950/30 rounded-full mb-4">
                        <x-heroicon-o-sparkles class="w-8 h-8 text-primary-500" />
                    </div>
                    <h3 class="text-base font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('expert-statistics::pbx.expert_statistics.ai_chat_welcome_title') }}
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 max-w-sm">
                        {{ __('expert-statistics::pbx.expert_statistics.ai_chat_welcome_description') }}
                    </p>

                    {{-- Starter suggestions --}}
                    <div class="mt-5 flex flex-wrap justify-center gap-2 max-w-md">
                        @foreach (__('expert-statistics::pbx.expert_statistics.ai_chat_starter_suggestions') as $suggestion)
                            <button
                                wire:click="useSuggestion('{{ addslashes($suggestion) }}')"
                                wire:loading.attr="disabled"
                                wire:target="useSuggestion,sendMessage"
                                class="px-3 py-1.5 rounded-xl text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300 hover:bg-primary-100 hover:text-primary-700 dark:hover:bg-primary-950/30 dark:hover:text-primary-400 transition-colors border border-gray-200 dark:border-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {{ $suggestion }}
                            </button>
                        @endforeach
                    </div>
                </div>
            @else
                @foreach ($messages as $message)
                    @if ($message['role'] === 'user')
                        {{-- User bubble --}}
                        <div class="flex justify-end">
                            <div class="max-w-[75%] bg-primary-500 text-white rounded-2xl rounded-br-sm px-4 py-2.5 text-sm leading-relaxed shadow-sm">
                                {{ $message['content'] ?? '' }}
                            </div>
                        </div>
                    @else
                        {{-- Assistant response --}}
                        <div class="flex justify-start">
                            <div class="max-w-[90%] space-y-3">

                                {{-- Title bar --}}
                                @if (! empty($message['title']))
                                    <div class="flex items-center gap-2">
                                        <x-heroicon-m-sparkles class="w-4 h-4 text-primary-500 flex-shrink-0" />
                                        <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">
                                            {{ $message['title'] }}
                                        </span>
                                    </div>
                                @endif

                                {{-- Text / explanation --}}
                                @if (! empty($message['explanation']))
                                    <div class="bg-gray-50 dark:bg-gray-900/50 rounded-2xl rounded-tl-sm px-4 py-3 text-sm text-gray-700 dark:text-gray-300 leading-relaxed">
                                        {!! nl2br(e($message['explanation'])) !!}
                                    </div>
                                @endif

                                {{-- kpi_card type --}}
                                @if (($message['type'] ?? '') === 'kpi_card' && ! empty($message['data']['kpis']))
                                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                        @foreach ($message['data']['kpis'] as $kpi)
                                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 px-3 py-2.5 text-center">
                                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $kpi['label'] ?? '' }}</p>
                                                <p class="text-lg font-bold text-gray-800 dark:text-gray-200 mt-0.5">
                                                    {{ $kpi['value'] ?? '' }}
                                                    @if (! empty($kpi['unit']))
                                                        <span class="text-xs font-normal text-gray-500">{{ $kpi['unit'] }}</span>
                                                    @endif
                                                </p>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                {{-- table type --}}
                                @if (($message['type'] ?? '') === 'table' && ! empty($message['data']['rows']))
                                    <div class="overflow-x-auto rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10">
                                        <table class="min-w-full text-xs divide-y divide-gray-200 dark:divide-gray-700">
                                            <thead class="bg-gray-50 dark:bg-gray-900/50">
                                                <tr>
                                                    @foreach ($message['data']['columns'] ?? [] as $col)
                                                        <th class="px-3 py-2 text-left font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                                                            {{ is_array($col) ? ($col['label'] ?? $col['key'] ?? '') : $col }}
                                                        </th>
                                                    @endforeach
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700/50">
                                                @foreach ($message['data']['rows'] as $row)
                                                    <tr>
                                                        @foreach ($message['data']['columns'] ?? [] as $col)
                                                            @php $key = is_array($col) ? ($col['key'] ?? '') : $col; @endphp
                                                            <td class="px-3 py-2 text-gray-700 dark:text-gray-300">
                                                                {{ $row[$key] ?? '' }}
                                                            </td>
                                                        @endforeach
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif

                                {{-- Insights --}}
                                @if (! empty($message['insights']))
                                    <div class="space-y-1.5">
                                        @foreach ($message['insights'] as $insight)
                                            @php
                                                $severity = is_array($insight) ? ($insight['severity'] ?? 'low') : 'low';
                                                $severityClasses = match ($severity) {
                                                    'high' => 'bg-red-50 border border-red-200 text-red-900 dark:bg-red-950/30 dark:border-red-800 dark:text-red-200',
                                                    'medium' => 'bg-orange-50 border border-orange-200 text-orange-900 dark:bg-orange-950/30 dark:border-orange-800 dark:text-orange-200',
                                                    default => 'bg-yellow-50 border border-yellow-200 text-yellow-900 dark:bg-yellow-950/30 dark:border-yellow-800 dark:text-yellow-200',
                                                };
                                            @endphp
                                            <div class="rounded-lg px-3 py-2 text-xs {{ $severityClasses }}">
                                                @if (is_array($insight))
                                                    @if (! empty($insight['action']))
                                                        <span class="font-semibold block">{{ $insight['action'] }}</span>
                                                    @endif
                                                    {{ $insight['description'] ?? '' }}
                                                @else
                                                    {{ $insight }}
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                {{-- Follow-up suggestions --}}
                                @if (! empty($message['follow_up_suggestions']))
                                    <div class="flex flex-wrap gap-2 mt-1">
                                        @foreach ($message['follow_up_suggestions'] as $suggestion)
                                            <button
                                                wire:click="useSuggestion('{{ addslashes($suggestion) }}')"
                                                wire:loading.attr="disabled"
                                                wire:target="useSuggestion,sendMessage"
                                                class="px-3 py-1.5 rounded-xl text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300 hover:bg-primary-100 hover:text-primary-700 dark:hover:bg-primary-950/30 dark:hover:text-primary-400 transition-colors border border-gray-200 dark:border-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
                                            >
                                                {{ $suggestion }}
                                            </button>
                                        @endforeach
                                    </div>
                                @endif

                            </div>
                        </div>
                    @endif
                @endforeach

                {{-- Loading bubble --}}
                <div wire:loading.flex wire:target="sendMessage,useSuggestion" class="justify-start">
                    <div class="bg-gray-100 dark:bg-gray-700 rounded-2xl rounded-tl-sm px-4 py-3">
                        <div class="flex items-center gap-1">
                            <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce [animation-delay:0ms]"></span>
                            <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce [animation-delay:150ms]"></span>
                            <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce [animation-delay:300ms]"></span>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Input area --}}
        <div class="px-5 py-4 border-t border-gray-200 dark:border-gray-700">
            <form wire:submit.prevent="sendMessage" class="flex items-end gap-3">
                <textarea
                    wire:model="inputMessage"
                    wire:keydown.enter.prevent="sendMessage"
                    rows="1"
                    placeholder="{{ __('expert-statistics::pbx.expert_statistics.ai_chat_input_placeholder') }}"
                    class="flex-1 resize-none rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-sm text-gray-800 dark:text-gray-200 placeholder-gray-400 px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition"
                    style="min-height: 2.5rem; max-height: 8rem;"
                    @class(['opacity-50 cursor-not-allowed' => $isLoading])
                    @disabled($isLoading)
                ></textarea>
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="sendMessage,useSuggestion"
                    class="flex-shrink-0 p-2.5 rounded-xl bg-primary-600 hover:bg-primary-700 text-white disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                >
                    <x-heroicon-m-paper-airplane class="w-5 h-5" />
                </button>
            </form>
            <p class="mt-2 text-[11px] text-gray-400 dark:text-gray-500">
                {{ __('expert-statistics::pbx.expert_statistics.ai_chat_disclaimer') }}
            </p>
        </div>

    </div>
</div>

{{-- Auto-scroll to bottom --}}
<script>
    document.addEventListener('livewire:updated', function () {
        const container = document.getElementById('ai-chat-messages');
        if (container) {
            container.scrollTop = container.scrollHeight;
        }
    });
</script>
