<div class="grid grid-cols-1 md:grid-cols-3 gap-x-8 gap-y-6">
    <div>
        <h2 class="text-base font-semibold leading-7 text-gray-900 dark:text-white">{{ __('expert-statistics::pbx.config.agent.title') }}</h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('expert-statistics::pbx.config.agent.description') }}</p>
    </div>
    <div class="md:col-span-2 rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-5 space-y-5">
        @if ($this->activeHostName())
            <div>
                <label for="status_custom1" class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">Custom 1</label>
                <input id="status_custom1" type="text" wire:model="agentForm.status_custom1"
                    class="block w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 dark:text-white shadow-sm text-sm focus:ring-primary-500 focus:border-primary-500" />
            </div>
            <div>
                <label for="status_custom2" class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">Custom 2</label>
                <input id="status_custom2" type="text" wire:model="agentForm.status_custom2"
                    class="block w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 dark:text-white shadow-sm text-sm focus:ring-primary-500 focus:border-primary-500" />
            </div>
            <div class="flex justify-end pt-2">
                <button wire:click="saveAgentConfig" type="button" @disabled(! $this->canModify())
                    class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 hover:bg-primary-700 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    {{ __('expert-statistics::pbx.config.save') }}
                </button>
            </div>
        @else
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('expert-statistics::pbx.config.noHost') }}</p>
        @endif
    </div>
</div>
