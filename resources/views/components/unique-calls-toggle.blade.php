@props([
    'enabled' => true,   // whether the toggle is clickable (false = grayed out)
    'checked' => false,  // current toggle state
    'wireModel' => null, // e.g. 'uniqueCalls' for wire:model.live
])

<div class="flex items-center gap-1.5">
    <label @class([
        'inline-flex items-center gap-2',
        'cursor-pointer' => $enabled,
        'cursor-not-allowed opacity-50' => ! $enabled,
    ])>
        <input
            type="checkbox"
            @if ($wireModel) wire:model.live="{{ $wireModel }}" @endif
            @if (! $enabled) disabled @endif
            @checked($checked)
            class="rounded border-gray-300 dark:border-gray-600 text-primary-600 disabled:opacity-40 disabled:cursor-not-allowed"
        />
        <span @class([
            'text-sm',
            'text-gray-700 dark:text-gray-300' => $enabled,
            'text-gray-400 dark:text-gray-600' => ! $enabled,
        ])>
            {{ __('expert-statistics::pbx.expert_statistics.toggle_unique_calls') }}
        </span>
    </label>

    {{-- Info icon with tooltip --}}
    <div x-data="{ show: false }" class="relative flex items-center">
        <svg
            @mouseenter="show = true"
            @mouseleave="show = false"
            xmlns="http://www.w3.org/2000/svg"
            width="16" height="16"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
            class="text-blue-400 hover:text-blue-600 cursor-help transition-colors duration-150 flex-shrink-0"
        >
            <circle cx="12" cy="12" r="10"/>
            <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
            <line x1="12" x2="12.01" y1="17" y2="17"/>
        </svg>

        <div
            x-show="show"
            x-cloak
            class="absolute z-50 top-full mt-2 left-1/2 pointer-events-none"
            style="width: 350px; min-width: 280px; transform: translateX(-50%);"
        >
            {{-- Arrow --}}
            <div class="absolute bottom-full left-1/2 -translate-x-1/2 w-0 h-0"
                 style="border-left: 8px solid transparent; border-right: 8px solid transparent; border-bottom: 8px solid #374151;"></div>

            <div class="bg-gray-700 text-white p-4 rounded-lg shadow-2xl border border-gray-600">
                <h3 class="text-sm font-bold mb-2">
                    {{ __('expert-statistics::pbx.expert_statistics.unique_calls_tooltip_title') }}
                </h3>
                <ul class="text-xs list-disc pl-4 space-y-2">
                    <li>
                        <span class="font-semibold">{{ __('expert-statistics::pbx.expert_statistics.unique_calls_tooltip_disabled_label') }}</span>
                        {{ __('expert-statistics::pbx.expert_statistics.unique_calls_tooltip_disabled') }}
                    </li>
                    <li>
                        <span class="font-semibold">{{ __('expert-statistics::pbx.expert_statistics.unique_calls_tooltip_enabled_label') }}</span>
                        {{ __('expert-statistics::pbx.expert_statistics.unique_calls_tooltip_enabled') }}
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
