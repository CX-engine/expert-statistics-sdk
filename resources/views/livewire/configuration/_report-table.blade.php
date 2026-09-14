<div class="space-y-8">

    {{-- Time interval sliders --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-x-8 gap-y-6">
        <div>
            <h2 class="text-base font-semibold leading-7 text-gray-900 dark:text-white">{{ __('expert-statistics::pbx.config.reportTable.intervalsTitle') }}</h2>
        </div>
        <div class="md:col-span-2">
            <div class="rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-5 space-y-5">
                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('expert-statistics::pbx.config.reportTable.answeredIn') }}</p>

                <div>
                    <p class="text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">{{ __('expert-statistics::pbx.config.reportTable.timeRange') }}</p>
                    <div class="flex items-center gap-3" x-data="{ val: {{ $reportTableForm['initial_interval'] ?? 20 }} }">
                        <input type="range" wire:model="reportTableForm.initial_interval" x-model.number="val"
                            min="10" max="140" step="10" class="w-full accent-primary-600" />
                        <span class="text-xs text-gray-500 dark:text-gray-400 w-16 text-right" x-text="val + 's'"></span>
                    </div>
                </div>

                <div>
                    <p class="text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">{{ __('expert-statistics::pbx.config.reportTable.timeGap') }}</p>
                    <div class="flex items-center gap-3" x-data="{ val: {{ $reportTableForm['following_interval'] ?? 20 }} }">
                        <input type="range" wire:model="reportTableForm.following_interval" x-model.number="val"
                            min="10" max="40" step="10" class="w-full accent-primary-600" />
                        <span class="text-xs text-gray-500 dark:text-gray-400 w-16 text-right" x-text="val + 's'"></span>
                    </div>
                </div>

                <div>
                    <p class="text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">{{ __('expert-statistics::pbx.config.reportTable.columns') }}</p>
                    <div class="flex items-center gap-3" x-data="{ val: {{ $reportTableForm['count_interval'] ?? 4 }} }">
                        <input type="range" wire:model="reportTableForm.count_interval" x-model.number="val"
                            min="1" max="5" step="1" class="w-full accent-primary-600" />
                        <span class="text-xs text-gray-500 dark:text-gray-400 w-16 text-right" x-text="val"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Visual alert thresholds --}}
    @php
        $alertSections = [
            'answered_percentage' => ['title' => __('expert-statistics::pbx.config.reportTable.answerRate'), 'unit' => '%'],
            'duration_avg_answer' => ['title' => __('expert-statistics::pbx.config.reportTable.avgWaitQueue'), 'unit' => 's'],
            'duration_avg_answer_user' => ['title' => __('expert-statistics::pbx.config.reportTable.avgWaitUser'), 'unit' => 's'],
            'duration_avg_call' => ['title' => __('expert-statistics::pbx.config.reportTable.avgCallDuration'), 'unit' => 's'],
            'ratio_solicitations' => ['title' => __('expert-statistics::pbx.config.reportTable.callRatio'), 'unit' => ''],
        ];
        $levelMeta = [
            'red' => ['label' => __('expert-statistics::pbx.config.reportTable.levelRed'), 'class' => 'text-red-500 bg-red-100 dark:bg-red-950/40 dark:text-red-400'],
            'orange' => ['label' => __('expert-statistics::pbx.config.reportTable.levelOrange'), 'class' => 'text-orange-600 bg-orange-100 dark:bg-orange-950/40 dark:text-orange-400'],
            'yellow' => ['label' => __('expert-statistics::pbx.config.reportTable.levelYellow'), 'class' => 'text-yellow-600 bg-yellow-50 dark:bg-yellow-950/40 dark:text-yellow-400'],
            'green' => ['label' => __('expert-statistics::pbx.config.reportTable.levelGreen'), 'class' => 'text-green-600 bg-green-100 dark:bg-green-950/40 dark:text-green-400'],
        ];
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-3 gap-x-8 gap-y-6">
        <div>
            <h2 class="text-base font-semibold leading-7 text-gray-900 dark:text-white">{{ __('expert-statistics::pbx.config.reportTable.visualAlertsTitle') }}</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('expert-statistics::pbx.config.reportTable.visualAlertsDesc') }}</p>
        </div>
        <div class="md:col-span-2 space-y-5">
            @foreach ($alertSections as $section => $meta)
                <div class="rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-4">
                    <div class="flex items-center justify-between mb-4">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $meta['title'] }}</p>
                        <div class="flex gap-3 text-xs">
                            <button wire:click="activateAll('{{ $section }}')" type="button" @disabled(! $this->canModify()) class="text-green-600 hover:underline disabled:opacity-50 disabled:cursor-not-allowed">
                                {{ __('expert-statistics::pbx.config.reportTable.activateAll') }}
                            </button>
                            <button wire:click="deactivateAll('{{ $section }}')" type="button" @disabled(! $this->canModify()) class="text-red-600 hover:underline disabled:opacity-50 disabled:cursor-not-allowed">
                                {{ __('expert-statistics::pbx.config.reportTable.deactivateAll') }}
                            </button>
                        </div>
                    </div>
                    <div class="space-y-2">
                        @foreach ($levelMeta as $level => $lmeta)
                            <div class="flex items-center justify-between gap-3 py-2 {{ ! $loop->last ? 'border-b border-gray-100 dark:border-gray-800' : '' }}">
                                <div class="flex items-center gap-2">
                                    <input type="number" wire:model="reportTableForm.{{ $section }}_{{ $level }}_value"
                                        class="w-24 rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 dark:text-white text-sm py-1 shadow-sm focus:ring-primary-500 focus:border-primary-500" />
                                    @if ($meta['unit'])
                                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ $meta['unit'] }}</span>
                                    @endif
                                </div>
                                <span class="text-xs font-semibold rounded-full px-3 py-1 {{ $lmeta['class'] }}">
                                    {{ $lmeta['label'] }}
                                </span>
                                <input type="checkbox" wire:model="reportTableForm.{{ $section }}_{{ $level }}_active"
                                    class="rounded border-gray-300 dark:border-gray-600 text-primary-600 h-5 w-5" />
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex justify-end">
        <button wire:click="saveReportTableForm" type="button" @disabled(! $this->canModify())
            class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 hover:bg-primary-700 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
            {{ __('expert-statistics::pbx.config.save') }}
        </button>
    </div>
</div>
