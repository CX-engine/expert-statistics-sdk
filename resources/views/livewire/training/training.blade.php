@php
    $card = 'rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10';
    $input = 'block w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 dark:text-white shadow-sm text-sm focus:ring-primary-500 focus:border-primary-500';
    $label = 'block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300';
    $primaryButton = 'inline-flex items-center justify-center gap-1.5 rounded-lg bg-primary-600 hover:bg-primary-700 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors disabled:opacity-50 disabled:cursor-not-allowed';
    $secondaryButton = 'inline-flex items-center justify-center gap-1.5 rounded-lg bg-white dark:bg-gray-800 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 shadow-sm ring-1 ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors disabled:opacity-50';
    $linkButton = 'text-sm font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400 hover:underline';
    $formatScore = fn ($score): string => rtrim(rtrim(number_format((float) $score, 2, '.', ''), '0'), '.');
@endphp

<x-expert-statistics::cluster-layout
    :title="__('expert-statistics::pbx.training.title')"
    :subtitle="__('expert-statistics::pbx.training.subtitle')"
>
    <div class="space-y-6">
        {{-- ── Request a key / enter a key ─────────────────────────── --}}
        @if (in_array($step, [\CXEngine\ExpertStatistics\Livewire\Training\Training::STEP_REQUEST, \CXEngine\ExpertStatistics\Livewire\Training\Training::STEP_KEY], true))
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="{{ $card }} p-5">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('expert-statistics::pbx.training.how_it_works') }}</h2>
                    <ol class="mt-4 space-y-3">
                        @foreach (['step_1', 'step_2', 'step_3', 'step_4'] as $index => $stepKey)
                            <li class="flex gap-3 text-sm text-gray-600 dark:text-gray-400">
                                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary-50 dark:bg-primary-500/10 text-xs font-semibold text-primary-700 dark:text-primary-400">{{ $index + 1 }}</span>
                                <span>{{ __('expert-statistics::pbx.training.'.$stepKey) }}</span>
                            </li>
                        @endforeach
                    </ol>
                </div>

                <div class="lg:col-span-2 {{ $card }} p-5">
                    @if ($step === \CXEngine\ExpertStatistics\Livewire\Training\Training::STEP_REQUEST)
                        <form wire:submit="requestKey" class="space-y-5">
                            <div>
                                <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('expert-statistics::pbx.training.request_title') }}</h2>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('expert-statistics::pbx.training.request_description') }}</p>
                            </div>

                            <div>
                                <label for="training-email" class="{{ $label }}">{{ __('expert-statistics::pbx.training.email') }}</label>
                                <input id="training-email" type="email" wire:model="email" autocomplete="email" class="{{ $input }}">
                                @error('email') <p class="mt-1 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="training-tenant" class="{{ $label }}">{{ __('expert-statistics::pbx.training.tenant') }}</label>
                                <select id="training-tenant" wire:model="tenantId" class="{{ $input }}">
                                    <option value="">{{ __('expert-statistics::pbx.training.tenant_placeholder') }}</option>
                                    @foreach ($this->tenants as $tenant)
                                        <option value="{{ $tenant['id'] }}">{{ $tenant['name'] }}</option>
                                    @endforeach
                                </select>
                                @error('tenantId') <p class="mt-1 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p> @enderror
                            </div>

                            <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
                                <button type="button" wire:click="showKeyForm" class="{{ $linkButton }}">{{ __('expert-statistics::pbx.training.have_key') }}</button>
                                <button type="submit" wire:loading.attr="disabled" wire:target="requestKey" class="{{ $primaryButton }}">
                                    <x-heroicon-o-envelope class="h-4 w-4" />
                                    {{ __('expert-statistics::pbx.training.send_key') }}
                                </button>
                            </div>
                        </form>
                    @else
                        <form wire:submit="startTraining" class="space-y-5">
                            <div>
                                <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('expert-statistics::pbx.training.key_title') }}</h2>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('expert-statistics::pbx.training.key_description') }}</p>
                            </div>

                            <div>
                                <label for="training-key-email" class="{{ $label }}">{{ __('expert-statistics::pbx.training.email') }}</label>
                                <input id="training-key-email" type="email" wire:model="email" autocomplete="email" class="{{ $input }}">
                                @error('email') <p class="mt-1 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="training-key" class="{{ $label }}">{{ __('expert-statistics::pbx.training.key') }}</label>
                                <input id="training-key" type="text" wire:model="accessKey" autocomplete="one-time-code"
                                    placeholder="{{ __('expert-statistics::pbx.training.key_placeholder') }}"
                                    class="{{ $input }} font-mono uppercase tracking-widest">
                                @error('accessKey') <p class="mt-1 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p> @enderror
                            </div>

                            <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
                                <button type="button" wire:click="showRequestForm" class="{{ $linkButton }}">{{ __('expert-statistics::pbx.training.request_new_key') }}</button>
                                <button type="submit" wire:loading.attr="disabled" wire:target="startTraining" class="{{ $primaryButton }}">
                                    <x-heroicon-o-play class="h-4 w-4" />
                                    {{ __('expert-statistics::pbx.training.start') }}
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        @endif

        {{-- ── Quiz ───────────────────────────────────────────────── --}}
        @if ($step === \CXEngine\ExpertStatistics\Livewire\Training\Training::STEP_QUIZ && $this->currentModule)
            @php
                $modules = $this->modules;
                $totalQuestions = count($questions);
                $answered = $this->answeredCount();
            @endphp

            <div class="{{ $card }} p-5 space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-2 text-sm">
                    <span class="font-medium text-gray-900 dark:text-white">{{ __('expert-statistics::pbx.training.module_progress', ['current' => $moduleIndex + 1, 'total' => count($modules)]) }}</span>
                    <span class="text-gray-500 dark:text-gray-400">{{ __('expert-statistics::pbx.training.answered_progress', ['answered' => $answered, 'total' => $totalQuestions]) }}</span>
                </div>
                <div class="h-2 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                    <div class="h-full rounded-full bg-primary-600 transition-all" style="width: {{ $totalQuestions > 0 ? round($answered / $totalQuestions * 100) : 0 }}%"></div>
                </div>
                <ol class="flex flex-wrap gap-2">
                    @foreach ($modules as $index => $module)
                        <li @class([
                            'rounded-full px-3 py-1 text-xs font-medium ring-1',
                            'bg-primary-600 text-white ring-primary-600' => $index === $moduleIndex,
                            'bg-success-50 text-success-700 ring-success-200 dark:bg-success-500/10 dark:text-success-400 dark:ring-success-500/20' => $index !== $moduleIndex && $this->isModuleComplete($index),
                            'bg-white text-gray-600 ring-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700' => $index !== $moduleIndex && ! $this->isModuleComplete($index),
                        ])>{{ $module['label'] }}</li>
                    @endforeach
                </ol>
            </div>

            <div class="{{ $card }} p-5 space-y-6" wire:key="training-module-{{ $moduleIndex }}">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $this->currentModule['label'] }}</h2>

                @foreach ($this->currentModule['questions'] as $questionIndex => $question)
                    <fieldset wire:key="training-question-{{ $question['id'] }}" class="space-y-3">
                        <legend class="text-sm text-gray-900 dark:text-white">
                            <span class="block text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('expert-statistics::pbx.training.question_number', ['number' => $questionIndex + 1]) }}</span>
                            <span class="mt-1 block font-medium">{{ $question['question'] }}</span>
                        </legend>
                        <div class="grid grid-cols-1 gap-2">
                            @foreach ($question['options'] as $option)
                                <label wire:key="training-option-{{ $option['id'] }}" @class([
                                    'flex cursor-pointer items-start gap-3 rounded-lg px-4 py-3 text-sm ring-1 transition-colors',
                                    'bg-primary-50 ring-primary-500 text-primary-900 dark:bg-primary-500/10 dark:text-primary-200' => $this->selectedOption($question['id']) === $option['id'],
                                    'bg-white ring-gray-200 text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:ring-gray-700 dark:text-gray-300 dark:hover:bg-gray-700' => $this->selectedOption($question['id']) !== $option['id'],
                                ])>
                                    <input type="radio" name="question-{{ $question['id'] }}" value="{{ $option['id'] }}"
                                        wire:model.live="answers.{{ $question['id'] }}"
                                        class="mt-0.5 h-4 w-4 border-gray-300 text-primary-600 focus:ring-primary-500">
                                    <span>{{ $option['label'] }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('answers.'.$question['id']) <p class="text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p> @enderror
                    </fieldset>
                @endforeach

                @error('quiz') <p class="text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p> @enderror

                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 dark:border-gray-800 pt-4">
                    <button type="button" wire:click="previousModule" @disabled($moduleIndex === 0) class="{{ $secondaryButton }}">
                        <x-heroicon-o-arrow-left class="h-4 w-4" />
                        {{ __('expert-statistics::pbx.training.previous') }}
                    </button>

                    @if ($this->isLastModule())
                        <button type="button" wire:click="finish" wire:loading.attr="disabled" wire:target="finish" class="{{ $primaryButton }}">
                            <x-heroicon-o-check-badge class="h-4 w-4" />
                            {{ __('expert-statistics::pbx.training.finish') }}
                        </button>
                    @else
                        <button type="button" wire:click="nextModule" wire:loading.attr="disabled" wire:target="nextModule" class="{{ $primaryButton }}">
                            {{ __('expert-statistics::pbx.training.next') }}
                            <x-heroicon-o-arrow-right class="h-4 w-4" />
                        </button>
                    @endif
                </div>
            </div>
        @endif

        {{-- ── Summary ────────────────────────────────────────────── --}}
        @if ($step === \CXEngine\ExpertStatistics\Livewire\Training\Training::STEP_SUMMARY)
            @php
                $result = $summary['training'] ?? [];
                $passed = (bool) ($result['passed'] ?? false);
            @endphp

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div @class([
                    $card, 'p-6 text-center',
                    'ring-success-500/40 dark:ring-success-500/30' => $passed,
                    'ring-warning-500/40 dark:ring-warning-500/30' => ! $passed,
                ])>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('expert-statistics::pbx.training.score') }}</p>
                    <p @class(['mt-2 text-5xl font-bold', 'text-success-600 dark:text-success-400' => $passed, 'text-warning-600 dark:text-warning-400' => ! $passed])>{{ $formatScore($result['score'] ?? 0) }}%</p>
                    <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">{{ __('expert-statistics::pbx.training.correct_answers', ['correct' => $result['correct_answers'] ?? 0, 'total' => $result['total_questions'] ?? 0]) }}</p>
                    <span @class([
                        'mt-4 inline-flex items-center gap-1 rounded-full px-3 py-1 text-sm font-semibold',
                        'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400' => $passed,
                        'bg-warning-50 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400' => ! $passed,
                    ])>
                        @if ($passed) <x-heroicon-o-check-badge class="h-4 w-4" /> @else <x-heroicon-o-exclamation-triangle class="h-4 w-4" /> @endif
                        {{ __('expert-statistics::pbx.training.'.($passed ? 'passed' : 'failed')) }}
                    </span>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ __('expert-statistics::pbx.training.pass_mark', ['score' => $result['pass_score'] ?? 70]) }}</p>
                    <p class="mt-4 text-xs text-gray-500 dark:text-gray-400">{{ __('expert-statistics::pbx.training.certificate_notice', ['email' => $result['email'] ?? $trainingEmail]) }}</p>
                </div>

                <div class="lg:col-span-2 {{ $card }} p-5">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('expert-statistics::pbx.training.by_module') }}</h2>
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-gray-500 dark:text-gray-400">
                                    <th class="py-2 pr-4 font-medium">{{ __('expert-statistics::pbx.training.module') }}</th>
                                    <th class="py-2 text-right font-medium">{{ __('expert-statistics::pbx.training.result') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @foreach ($summary['modules'] ?? [] as $module)
                                    <tr>
                                        <td class="py-2 pr-4 text-gray-900 dark:text-white">{{ $module['label'] }}</td>
                                        <td @class([
                                            'py-2 text-right font-semibold',
                                            'text-success-600 dark:text-success-400' => $module['correct'] === $module['total'],
                                            'text-gray-700 dark:text-gray-300' => $module['correct'] !== $module['total'],
                                        ])>{{ $module['correct'] }} / {{ $module['total'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="{{ $card }} p-5 space-y-6">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('expert-statistics::pbx.training.answers_title') }}</h2>

                @foreach (collect($summary['questions'] ?? [])->groupBy('module') as $moduleQuestions)
                    <section class="space-y-4" wire:key="training-summary-{{ $moduleQuestions->first()['module'] }}">
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-primary-700 dark:text-primary-400">{{ $moduleQuestions->first()['module_label'] }}</h3>

                        @foreach ($moduleQuestions as $question)
                            @php
                                $options = collect($question['options']);
                                $selected = $options->firstWhere('id', $question['selected_option_id']);
                                $correct = $options->firstWhere('id', $question['correct_option_id']);
                            @endphp
                            <div class="flex gap-3 rounded-lg p-4 ring-1 ring-gray-100 dark:ring-gray-800">
                                @if ($question['is_correct'])
                                    <x-heroicon-s-check-circle class="h-5 w-5 shrink-0 text-success-500" />
                                @else
                                    <x-heroicon-s-x-circle class="h-5 w-5 shrink-0 text-danger-500" />
                                @endif
                                <div class="min-w-0 space-y-2 text-sm">
                                    <p class="font-medium text-gray-900 dark:text-white">{{ $question['question'] }}</p>
                                    <p @class(['text-success-700 dark:text-success-400' => $question['is_correct'], 'text-danger-700 dark:text-danger-400' => ! $question['is_correct']])>
                                        <span class="font-medium">{{ __('expert-statistics::pbx.training.your_answer') }}</span>
                                        {{ $selected['label'] ?? __('expert-statistics::pbx.training.not_answered') }}
                                    </p>
                                    @unless ($question['is_correct'])
                                        <p class="text-success-700 dark:text-success-400">
                                            <span class="font-medium">{{ __('expert-statistics::pbx.training.correct_answer') }}</span>
                                            {{ $correct['label'] ?? '' }}
                                        </p>
                                    @endunless
                                    @if (! empty($question['explanation']))
                                        <p class="text-gray-500 dark:text-gray-400">{{ $question['explanation'] }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </section>
                @endforeach

                <div class="flex justify-end border-t border-gray-100 dark:border-gray-800 pt-4">
                    <button type="button" wire:click="restart" class="{{ $secondaryButton }}">
                        <x-heroicon-o-arrow-path class="h-4 w-4" />
                        {{ __('expert-statistics::pbx.training.restart') }}
                    </button>
                </div>
            </div>
        @endif
    </div>
</x-expert-statistics::cluster-layout>
