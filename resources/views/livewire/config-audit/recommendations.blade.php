<div class="space-y-4">
    <div class="flex flex-wrap items-center gap-2">
        <select wire:model.live="findingCategory" aria-label="{{ $t('filter_category') }}"
                class="rounded-lg border-gray-200 dark:border-gray-700 dark:bg-gray-800 text-sm py-1.5">
            <option value="">{{ $t('all_categories') }}</option>
            @foreach (['probable_error', 'to_check', 'optimisation'] as $category)
                <option value="{{ $category }}">{{ $t('categories.'.$category) }}</option>
            @endforeach
        </select>
        <select wire:model.live="findingArea" aria-label="{{ $t('filter_area') }}"
                class="rounded-lg border-gray-200 dark:border-gray-700 dark:bg-gray-800 text-sm py-1.5">
            <option value="">{{ $t('all_areas') }}</option>
            @foreach (['routing', 'queues', 'hours', 'call_flow_apps', 'general'] as $area)
                <option value="{{ $area }}">{{ $t('areas.'.$area) }}</option>
            @endforeach
        </select>
        <input type="search" wire:model.live.debounce.400ms="findingSearch" placeholder="{{ $t('search_findings') }}"
               class="flex-1 min-w-48 rounded-lg border-gray-200 dark:border-gray-700 dark:bg-gray-800 text-sm py-1.5" />
    </div>

    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $t('recommendations_help') }}</p>

    <div class="space-y-3" wire:loading.class="opacity-50" wire:target="findingCategory, findingArea, findingSearch, goToPage">
        @forelse ($findings['data'] ?? [] as $finding)
            @include('expert-statistics::livewire.config-audit.finding-card', ['finding' => $finding, 'key' => 'finding-'.($finding['id'] ?? $loop->index)])
        @empty
            <div class="bg-white dark:bg-gray-800 rounded-lg ring-1 ring-gray-950/5 dark:ring-white/10 px-4 py-8 text-center text-sm text-gray-500">
                {{ $t('no_matching_findings') }}
            </div>
        @endforelse
    </div>

    @include('expert-statistics::livewire.config-audit.pagination', ['meta' => $findings['meta'] ?? [], 'list' => 'findings'])
</div>
