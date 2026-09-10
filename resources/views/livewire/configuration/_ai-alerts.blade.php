<div class="space-y-8">

    {{-- General settings --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-x-8 gap-y-6">
        <div>
            <h2 class="text-base font-semibold leading-7 text-gray-900 dark:text-white">{{ __('expert-statistics::pbx.config.aiAlerts.generalSection') }}</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('expert-statistics::pbx.config.aiAlerts.description') }}</p>
        </div>
        <div class="md:col-span-2 space-y-4">
            {{-- Enabled toggle --}}
            <div class="flex items-center justify-between rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-4 py-3 shadow-sm">
                <div>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ __('expert-statistics::pbx.config.aiAlerts.enabled') }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ __('expert-statistics::pbx.config.aiAlerts.enabledDesc') }}</p>
                </div>
                <button type="button" wire:click="$set('aiAlertSettings.enabled', {{ ($aiAlertSettings['enabled'] ?? true) ? 'false' : 'true' }})"
                    class="{{ ($aiAlertSettings['enabled'] ?? true) ? 'bg-primary-600' : 'bg-gray-200 dark:bg-gray-600' }} relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors focus:outline-none">
                    <span class="{{ ($aiAlertSettings['enabled'] ?? true) ? 'translate-x-5' : 'translate-x-0' }} inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform"></span>
                </button>
            </div>

            {{-- Check interval & language --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">{{ __('expert-statistics::pbx.config.aiAlerts.checkInterval') }}</label>
                    <input type="number" wire:model="aiAlertSettings.check_interval_minutes" min="15" max="1440"
                        class="block w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 dark:text-white text-sm shadow-sm focus:ring-primary-500 focus:border-primary-500" />
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">{{ __('expert-statistics::pbx.config.aiAlerts.language') }}</label>
                    <select wire:model="aiAlertSettings.language"
                        class="block w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 dark:text-white text-sm shadow-sm focus:ring-primary-500 focus:border-primary-500">
                        <option value="fr">Français</option>
                        <option value="en">English</option>
                    </select>
                </div>
            </div>

            {{-- Notification email --}}
            <div>
                <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">{{ __('expert-statistics::pbx.config.aiAlerts.notificationEmail') }}</label>
                <input type="email" wire:model="aiAlertSettings.notification_email" placeholder="alerts@example.com"
                    class="block w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 dark:text-white text-sm shadow-sm focus:ring-primary-500 focus:border-primary-500" />
            </div>
        </div>
    </div>

    {{-- Thresholds --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-x-8 gap-y-6">
        <div>
            <h2 class="text-base font-semibold leading-7 text-gray-900 dark:text-white">{{ __('expert-statistics::pbx.config.aiAlerts.thresholdsSection') }}</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('expert-statistics::pbx.config.aiAlerts.thresholdsDesc') }}</p>
        </div>
        <div class="md:col-span-2 space-y-4">
            @php
                // Full class strings (not built from a dynamic `{color}` segment) so
                // Tailwind's content scanner can actually see and keep them.
                $amber = 'text-amber-600 dark:text-amber-400';
                $red = 'text-red-600 dark:text-red-400';
                $primary = 'text-primary-600 dark:text-primary-400';

                $thresholdGroups = [
                    [
                        'title' => __('expert-statistics::pbx.config.aiAlerts.abandonRate'),
                        'fields' => [
                            ['key' => 'abandon_rate_warning', 'label' => __('expert-statistics::pbx.config.aiAlerts.levelWarning'), 'colorClass' => $amber, 'unit' => '%'],
                            ['key' => 'abandon_rate_critical', 'label' => __('expert-statistics::pbx.config.aiAlerts.levelCritical'), 'colorClass' => $red, 'unit' => '%'],
                        ],
                    ],
                    [
                        'title' => __('expert-statistics::pbx.config.aiAlerts.notAnsweredRate'),
                        'fields' => [
                            ['key' => 'not_answered_rate_warning', 'label' => __('expert-statistics::pbx.config.aiAlerts.levelWarning'), 'colorClass' => $amber, 'unit' => '%'],
                            ['key' => 'not_answered_rate_critical', 'label' => __('expert-statistics::pbx.config.aiAlerts.levelCritical'), 'colorClass' => $red, 'unit' => '%'],
                        ],
                    ],
                    [
                        'title' => __('expert-statistics::pbx.config.aiAlerts.abandonPreanswer'),
                        'fields' => [
                            ['key' => 'abandoned_preanswer_rate_warning', 'label' => __('expert-statistics::pbx.config.aiAlerts.levelWarning'), 'colorClass' => $amber, 'unit' => '%'],
                        ],
                    ],
                    [
                        'title' => __('expert-statistics::pbx.config.aiAlerts.waitTime'),
                        'fields' => [
                            ['key' => 'wait_time_warning', 'label' => __('expert-statistics::pbx.config.aiAlerts.levelWarning'), 'colorClass' => $amber, 'unit' => 's'],
                            ['key' => 'wait_time_critical', 'label' => __('expert-statistics::pbx.config.aiAlerts.levelCritical'), 'colorClass' => $red, 'unit' => 's'],
                        ],
                    ],
                    [
                        'title' => __('expert-statistics::pbx.config.aiAlerts.changeThresholds'),
                        'fields' => [
                            ['key' => 'volume_change_percent', 'label' => __('expert-statistics::pbx.config.aiAlerts.volumeChangePercent'), 'colorClass' => $primary, 'unit' => '%'],
                            ['key' => 'abandon_rate_change_percent', 'label' => __('expert-statistics::pbx.config.aiAlerts.abandonRateChangePercent'), 'colorClass' => $primary, 'unit' => '%'],
                        ],
                    ],
                ];
            @endphp

            @foreach ($thresholdGroups as $group)
                <div class="p-4 bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                    <p class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-3">{{ $group['title'] }}</p>
                    <div class="grid grid-cols-{{ count($group['fields']) === 1 ? '1' : '2' }} gap-4">
                        @foreach ($group['fields'] as $field)
                            <div>
                                <label class="block text-xs font-medium {{ $field['colorClass'] }} mb-1">{{ $field['label'] }}</label>
                                <div class="flex items-center gap-2">
                                    <input type="number" wire:model="aiAlertSettings.thresholds.{{ $field['key'] }}" min="0" max="500" step="0.5"
                                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 dark:text-white text-sm py-1.5 shadow-sm focus:ring-primary-500 focus:border-primary-500" />
                                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ $field['unit'] }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex justify-end">
        <button wire:click="saveAiAlertSettings" type="button"
            class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 hover:bg-primary-700 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors">
            {{ __('expert-statistics::pbx.config.save') }}
        </button>
    </div>
</div>
