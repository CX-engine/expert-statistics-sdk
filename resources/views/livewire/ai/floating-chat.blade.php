@php
    $windowWidth = $isExpanded ? 'w-[36rem]' : 'w-96';
    $windowHeight = $isExpanded ? 'h-[38rem]' : 'h-[32rem]';
@endphp

<div class="fixed bottom-6 right-6 z-50 flex flex-col items-end gap-3">

    {{-- ── Floating chat window ──────────────────────────────────────────── --}}
    @if ($isOpen)
        <div @class([
            'flex flex-col bg-white dark:bg-gray-900 rounded-2xl shadow-2xl ring-1 ring-gray-950/10 dark:ring-white/10 overflow-hidden transition-all duration-200',
            $windowWidth,
            $windowHeight,
        ])>
            {{-- ── Header ──────────────────────────────────────────────── --}}
            <div class="flex-shrink-0 flex items-center gap-1 px-3 py-2 bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">

                {{-- Tabs --}}
                <div class="flex items-center gap-0.5 flex-1 min-w-0">
                    @foreach ([
                        ['key' => 'chat', 'label' => __('expert-statistics::pbx.expert_statistics.ai_widget_tab_chat'), 'icon' => 'heroicon-m-chat-bubble-left-right'],
                        ['key' => 'history', 'label' => __('expert-statistics::pbx.expert_statistics.ai_widget_tab_history'), 'icon' => 'heroicon-m-clock'],
                    ] as $t)
                        <button
                            wire:click="switchTab('{{ $t['key'] }}')"
                            @class([
                                'flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-medium transition-colors whitespace-nowrap',
                                'bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 shadow-sm' => $tab === $t['key'],
                                'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700/50' => $tab !== $t['key'],
                            ])
                        >
                            @svg($t['icon'], 'w-3.5 h-3.5 flex-shrink-0')
                            <span>{{ $t['label'] }}</span>
                        </button>
                    @endforeach
                </div>

                {{-- Expand / collapse --}}
                <button
                    wire:click="toggleExpand"
                    class="p-1.5 rounded-lg text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors flex-shrink-0"
                    title="{{ $isExpanded ? __('expert-statistics::pbx.expert_statistics.ai_widget_collapse') : __('expert-statistics::pbx.expert_statistics.ai_widget_expand') }}"
                >
                    @if ($isExpanded)
                        <x-heroicon-m-arrows-pointing-in class="w-4 h-4" />
                    @else
                        <x-heroicon-m-arrows-pointing-out class="w-4 h-4" />
                    @endif
                </button>

                {{-- Close --}}
                <button
                    wire:click="close"
                    class="p-1.5 rounded-lg text-gray-400 dark:text-gray-500 hover:text-red-500 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/30 transition-colors flex-shrink-0"
                    title="{{ __('expert-statistics::pbx.expert_statistics.ai_widget_close') }}"
                >
                    <x-heroicon-m-x-mark class="w-4 h-4" />
                </button>
            </div>

            {{-- ── Error banner ─────────────────────────────────────────── --}}
            @if ($errorMessage)
                <div class="flex-shrink-0 mx-3 mt-2 rounded-lg bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-900 px-3 py-2 text-xs text-red-700 dark:text-red-400">
                    {{ $errorMessage }}
                </div>
            @endif

            {{-- CHAT tab ─────────────────────────────────────────────────── --}}
            @if ($tab === 'chat')
                <div class="flex flex-col flex-1 overflow-hidden min-h-0">

                    {{-- Messages --}}
                    <div class="relative flex-1 overflow-y-auto px-3 py-3 space-y-3" id="ai-widget-messages">
                        <div wire:loading.flex wire:target="selectConversation" class="absolute inset-0 z-10 items-center justify-center bg-white/75 dark:bg-gray-900/75">
                            <div class="flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 bg-primary-400 rounded-full animate-bounce [animation-delay:0ms]"></span>
                                <span class="w-1.5 h-1.5 bg-primary-400 rounded-full animate-bounce [animation-delay:150ms]"></span>
                                <span class="w-1.5 h-1.5 bg-primary-400 rounded-full animate-bounce [animation-delay:300ms]"></span>
                            </div>
                        </div>

                        @if (empty($messages))
                            <div class="flex flex-col items-center justify-center h-full text-center py-6">
                                <div class="p-3 bg-primary-50 dark:bg-primary-950/30 rounded-full mb-3">
                                    <x-heroicon-o-sparkles class="w-6 h-6 text-primary-500" />
                                </div>
                                <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                    {{ __('expert-statistics::pbx.expert_statistics.ai_widget_welcome_title') }}
                                </p>
                                <p class="text-xs text-gray-400 dark:text-gray-500 max-w-xs leading-relaxed">
                                    {{ __('expert-statistics::pbx.expert_statistics.ai_widget_welcome_description') }}
                                </p>
                                <div class="mt-4 flex flex-wrap justify-center gap-1.5 max-w-xs">
                                    @foreach (__('expert-statistics::pbx.expert_statistics.ai_widget_starter_suggestions') as $s)
                                        <button
                                            wire:click="useSuggestion('{{ addslashes($s) }}')"
                                            wire:loading.attr="disabled"
                                            wire:target="useSuggestion,sendMessage"
                                            class="px-2.5 py-1 rounded-lg text-xs text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-800 hover:bg-primary-100 hover:text-primary-700 dark:hover:bg-primary-950/30 dark:hover:text-primary-400 border border-gray-200 dark:border-gray-700 transition-colors text-left disabled:opacity-50 disabled:cursor-not-allowed"
                                        >{{ $s }}</button>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            @foreach ($messages as $msg)
                                @if ($msg['role'] === 'user')
                                    <div class="flex justify-end">
                                        <div class="max-w-[85%] bg-primary-500 text-white rounded-2xl rounded-br-sm px-3 py-2 text-xs leading-relaxed shadow-sm">
                                            {{ $msg['content'] ?? '' }}
                                        </div>
                                    </div>
                                @else
                                    <div class="flex justify-start">
                                        <div class="max-w-[90%] space-y-2">

                                            @if (! empty($msg['title']))
                                                <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-1">
                                                    <x-heroicon-m-sparkles class="w-3.5 h-3.5 text-primary-500 flex-shrink-0" />
                                                    {{ $msg['title'] }}
                                                </p>
                                            @endif

                                            @if (! empty($msg['explanation']))
                                                <div class="bg-gray-100 dark:bg-gray-800 rounded-2xl rounded-tl-sm px-3 py-2 text-xs text-gray-700 dark:text-gray-300 leading-relaxed">
                                                    {!! nl2br(e($msg['explanation'])) !!}
                                                </div>
                                            @endif

                                            @if (($msg['type'] ?? '') === 'kpi_card' && ! empty($msg['data']['kpis']))
                                                <div class="grid grid-cols-2 gap-2">
                                                    @foreach (array_slice($msg['data']['kpis'], 0, 4) as $kpi)
                                                        <div class="bg-gray-50 dark:bg-gray-800 rounded-xl px-2.5 py-2 text-center ring-1 ring-gray-950/5 dark:ring-white/10">
                                                            <p class="text-[10px] text-gray-500 dark:text-gray-400 truncate">{{ $kpi['label'] ?? '' }}</p>
                                                            <p class="text-sm font-bold text-gray-800 dark:text-gray-200">{{ $kpi['value'] ?? '' }}</p>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif

                                            @if (! empty($msg['insights']))
                                                <div class="space-y-1">
                                                    @foreach (array_slice($msg['insights'], 0, 3) as $insight)
                                                        <div class="flex items-start gap-1.5 text-[11px] text-gray-500 dark:text-gray-400">
                                                            <x-heroicon-m-light-bulb class="w-3 h-3 text-yellow-500 shrink-0 mt-0.5" />
                                                            <span>{{ is_array($insight) ? ((! empty($insight['action']) ? $insight['action'].': ' : '').($insight['description'] ?? '')) : $insight }}</span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif

                                            @if (! empty($msg['follow_up_suggestions']))
                                                <div class="flex flex-wrap gap-1.5">
                                                    @foreach (array_slice($msg['follow_up_suggestions'], 0, 3) as $s)
                                                        <button
                                                            wire:click="useSuggestion('{{ addslashes($s) }}')"
                                                            wire:loading.attr="disabled"
                                                            wire:target="useSuggestion,sendMessage"
                                                            class="px-2.5 py-1 rounded-lg text-[11px] font-medium text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-800 hover:bg-primary-100 hover:text-primary-700 dark:hover:bg-primary-950/30 dark:hover:text-primary-400 border border-gray-200 dark:border-gray-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                                        >{{ $s }}</button>
                                                    @endforeach
                                                </div>
                                            @endif

                                        </div>
                                    </div>
                                @endif
                            @endforeach

                            {{-- Typing indicator --}}
                            <div wire:loading.flex wire:target="sendMessage,useSuggestion" class="justify-start">
                                <div class="bg-gray-100 dark:bg-gray-800 rounded-2xl rounded-tl-sm px-3 py-2">
                                    <span class="flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce [animation-delay:0ms]"></span>
                                        <span class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce [animation-delay:150ms]"></span>
                                        <span class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce [animation-delay:300ms]"></span>
                                    </span>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Input --}}
                    <div class="flex-shrink-0 px-3 py-2.5 border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
                        <form wire:submit.prevent="sendMessage" class="flex items-end gap-2">
                            <textarea
                                wire:model="inputMessage"
                                wire:keydown.enter.prevent="sendMessage"
                                rows="1"
                                placeholder="{{ __('expert-statistics::pbx.expert_statistics.ai_widget_input_placeholder') }}"
                                class="flex-1 resize-none rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-xs text-gray-800 dark:text-gray-200 placeholder-gray-400 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                style="min-height: 2rem; max-height: 6rem;"
                                @class(['opacity-50 cursor-not-allowed' => $isSending])
                                @disabled($isSending)
                            ></textarea>
                            <button
                                type="submit"
                                wire:loading.attr="disabled"
                                wire:target="sendMessage,useSuggestion"
                                class="flex-shrink-0 p-2 rounded-xl bg-primary-600 hover:bg-primary-700 text-white disabled:opacity-50 transition-colors"
                            >
                                <x-heroicon-m-paper-airplane class="w-4 h-4" />
                            </button>
                        </form>
                    </div>

                </div>

            {{-- HISTORY tab ───────────────────────────────────────────────── --}}
            @elseif ($tab === 'history')
                <div class="flex-1 overflow-y-auto">
                    <div wire:loading.flex wire:target="switchTab" class="items-center justify-center py-8">
                        <div class="flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 bg-primary-400 rounded-full animate-bounce [animation-delay:0ms]"></span>
                            <span class="w-1.5 h-1.5 bg-primary-400 rounded-full animate-bounce [animation-delay:150ms]"></span>
                            <span class="w-1.5 h-1.5 bg-primary-400 rounded-full animate-bounce [animation-delay:300ms]"></span>
                        </div>
                    </div>
                    <div wire:loading.remove wire:target="switchTab">
                        <div class="px-3 pt-3 pb-2">
                            <button
                                wire:click="newConversation"
                                class="w-full flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-xs font-medium bg-primary-50 dark:bg-primary-950/30 text-primary-700 dark:text-primary-400 hover:bg-primary-100 dark:hover:bg-primary-950/50 border border-primary-200 dark:border-primary-800 transition-colors"
                            >
                                <x-heroicon-m-plus class="w-3.5 h-3.5" />
                                {{ __('expert-statistics::pbx.expert_statistics.ai_widget_new_conversation') }}
                            </button>
                        </div>

                        @if (empty($conversations))
                            <div class="px-3 py-10 text-center">
                                <x-heroicon-o-chat-bubble-left class="w-8 h-8 mx-auto text-gray-200 dark:text-gray-700 mb-2" />
                                <p class="text-xs text-gray-400 dark:text-gray-500">{{ __('expert-statistics::pbx.expert_statistics.ai_widget_no_history') }}</p>
                            </div>
                        @else
                            @foreach ($conversations as $conv)
                                @php
                                    $convId = (string) ($conv['uuid'] ?? $conv['id'] ?? '');
                                    $convTitle = $conv['title'] ?? null;
                                    $msgCount = $conv['messages_count'] ?? null;
                                    $timestamp = $conv['updated_at'] ?? $conv['created_at'] ?? null;
                                @endphp
                                <button
                                    wire:click="selectConversation('{{ $convId }}')"
                                    wire:key="ai-widget-conv-{{ $convId }}"
                                    wire:loading.class="opacity-50 pointer-events-none"
                                    wire:target="selectConversation"
                                    class="w-full text-left px-3 py-3 border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors"
                                >
                                    <p class="text-xs font-medium text-gray-700 dark:text-gray-300 truncate leading-snug">
                                        {{ $convTitle ?? __('expert-statistics::pbx.expert_statistics.ai_widget_untitled') }}
                                    </p>
                                    <div class="flex items-center gap-1.5 mt-0.5">
                                        @if ($timestamp)
                                            <span class="text-[11px] text-gray-400 dark:text-gray-500">
                                                {{ \Carbon\Carbon::parse($timestamp)->diffForHumans() }}
                                            </span>
                                        @endif
                                        @if ($msgCount)
                                            <span class="text-[10px] font-semibold text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-950/30 px-1.5 py-0.5 rounded-full leading-none border border-primary-100 dark:border-primary-800">
                                                {{ $msgCount }}
                                            </span>
                                        @endif
                                    </div>
                                </button>
                            @endforeach
                        @endif
                    </div>
                </div>
            @endif

        </div>
    @endif

    {{-- ── FAB trigger button ──────────────────────────────────────────────── --}}
    <button
        wire:click="toggle"
        @class([
            'group relative flex items-center justify-center w-11 h-11 rounded-full shadow-md transition-all duration-200 text-white opacity-75 hover:opacity-100 hover:shadow-lg focus:opacity-100',
            'bg-linear-to-br from-teal-600 to-purple-500' => ! $isOpen,
            'bg-gray-700 dark:bg-gray-600 hover:bg-gray-800 dark:hover:bg-gray-500' => $isOpen,
        ])
        title="{{ $isOpen ? __('expert-statistics::pbx.expert_statistics.ai_widget_close') : __('expert-statistics::pbx.expert_statistics.ai_widget_open') }}"
        aria-label="{{ $isOpen ? __('expert-statistics::pbx.expert_statistics.ai_widget_close') : __('expert-statistics::pbx.expert_statistics.ai_widget_open') }}"
    >
        @if ($isOpen)
            <x-heroicon-m-x-mark class="w-5 h-5 transition-transform duration-200" />
        @else
            <x-heroicon-o-sparkles class="w-5 h-5 transition-transform duration-200 group-hover:scale-110" />
        @endif
    </button>

</div>

{{-- Auto-scroll chat to bottom --}}
<script>
    document.addEventListener('livewire:updated', function () {
        const el = document.getElementById('ai-widget-messages');
        if (el) el.scrollTop = el.scrollHeight;
    });
</script>
