<div class="max-w-5xl mx-auto space-y-6 pb-8">

    {{-- Tab bar --}}
    <div class="flex bg-gray-100 dark:bg-gray-800 rounded-xl p-1 gap-1 overflow-x-auto">
        @php
            $tabs = [
                'agent' => ['label' => __('expert-statistics::pbx.config.tab.agent'), 'icon' => 'heroicon-m-user'],
                'queue' => ['label' => __('expert-statistics::pbx.config.tab.queue'), 'icon' => 'heroicon-m-queue-list'],
                'report-table' => ['label' => __('expert-statistics::pbx.config.tab.reportTable'), 'icon' => 'heroicon-m-table-cells'],
                'ai-alerts' => ['label' => __('expert-statistics::pbx.config.tab.aiAlerts'), 'icon' => 'heroicon-m-bell-alert'],
                'groups' => ['label' => __('expert-statistics::pbx.config.tab.groups'), 'icon' => 'heroicon-m-squares-2x2'],
                'wallboard' => ['label' => __('expert-statistics::pbx.config.tab.wallboard'), 'icon' => 'heroicon-m-tv'],
            ];
        @endphp

        @foreach ($tabs as $key => $meta)
            <button wire:click="switchTab('{{ $key }}')" type="button"
                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium whitespace-nowrap transition-all duration-150
                    {{ $tab === $key
                        ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm'
                        : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200' }}">
                <x-dynamic-component :component="$meta['icon']" class="w-4 h-4" />
                {{ $meta['label'] }}
            </button>
        @endforeach
    </div>

    {{-- Feedback banner --}}
    @if ($statusMessage)
        <div @class([
            'rounded-xl border px-4 py-3 text-sm',
            'bg-emerald-50 dark:bg-emerald-950/30 border-emerald-200 dark:border-emerald-900 text-emerald-700 dark:text-emerald-400' => $statusType === 'success',
            'bg-red-50 dark:bg-red-950/30 border-red-200 dark:border-red-900 text-red-700 dark:text-red-400' => $statusType === 'error',
            'bg-amber-50 dark:bg-amber-950/30 border-amber-200 dark:border-amber-900 text-amber-700 dark:text-amber-400' => $statusType === 'warning',
        ])>
            {{ $statusMessage }}
        </div>
    @endif

    <div class="px-1">
        @if ($tab === 'agent')
            @include('expert-statistics::livewire.configuration._agent')
        @elseif ($tab === 'queue')
            @include('expert-statistics::livewire.configuration._queue')
        @elseif ($tab === 'report-table')
            @include('expert-statistics::livewire.configuration._report-table')
        @elseif ($tab === 'ai-alerts')
            @include('expert-statistics::livewire.configuration._ai-alerts')
        @elseif ($tab === 'groups')
            @include('expert-statistics::livewire.configuration._groups')
        @elseif ($tab === 'wallboard')
            @include('expert-statistics::livewire.configuration._wallboard')
        @endif
    </div>

</div>
