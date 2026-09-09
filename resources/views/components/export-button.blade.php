@props(['url'])

@if ($url !== '#')
    <a
        href="{{ $url }}"
        title="{{ __('expert-statistics::pbx.expert_statistics.export_excel') }}"
        class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-gray-500 dark:text-gray-400 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 hover:bg-white dark:hover:bg-gray-700 hover:text-gray-800 dark:hover:text-gray-100 transition shrink-0"
    >
        <x-heroicon-o-arrow-down-tray class="w-4 h-4 shrink-0" />
        <span class="hidden sm:inline">{{ __('expert-statistics::pbx.expert_statistics.export_excel') }}</span>
    </a>
@endif
