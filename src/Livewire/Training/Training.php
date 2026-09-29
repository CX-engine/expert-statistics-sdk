<?php

declare(strict_types=1);

namespace CXEngine\ExpertStatistics\Livewire\Training;

use CXEngine\ExpertStatistics\Concerns\AuthorizesExpertStatisticsAccess;
use CXEngine\ExpertStatistics\Contracts\ProvidesTrainingParticipants;
use CXEngine\ExpertStatistics\Exceptions\InvalidTrainingKeyException;
use CXEngine\ExpertStatistics\Services\ExpertStatisticsService;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Throwable;

/**
 * Expert Statistics training: a multiple-choice quiz, split into product
 * modules, that checks a participant knows how to use and configure Expert
 * Statistics.
 *
 * Flow: `request` (email + client tenant; the email must be invited to that
 * tenant, checked host-app side through ProvidesTrainingParticipants, then
 * the backend emails a key) → `key` (email + key unlock the training) →
 * `quiz` (one module at a time, answers saved on every step so a reload can
 * resume) → `summary` (score, per-module results, every answer; the backend
 * also emails the certificate).
 *
 * Only offered when the host app says so (isTrainingAvailable(), e.g. demo
 * tenant + host only) - 404 otherwise. No activation requirement: this page
 * doesn't read call data.
 */
class Training extends Component
{
    use AuthorizesExpertStatisticsAccess;

    public const STEP_REQUEST = 'request';

    public const STEP_KEY = 'key';

    public const STEP_QUIZ = 'quiz';

    public const STEP_SUMMARY = 'summary';

    /** Key requests allowed per email, per decay window. */
    private const MAX_KEY_REQUESTS = 3;

    private const KEY_REQUEST_DECAY_SECONDS = 600;

    #[Locked]
    public string $step = self::STEP_REQUEST;

    public string $email = '';

    public string $tenantId = '';

    public string $accessKey = '';

    /** Credentials the current training was unlocked with. */
    #[Locked]
    public ?string $trainingEmail = null;

    #[Locked]
    public ?string $trainingKey = null;

    /** @var array<string, mixed> */
    #[Locked]
    public array $training = [];

    /** @var array<int, array{id: int, module: string, module_label: string, question: string, options: array<int, array{id: int, label: string}>}> */
    #[Locked]
    public array $questions = [];

    /** @var array<int|string, int|string> question id => selected option id */
    public array $answers = [];

    #[Locked]
    public int $moduleIndex = 0;

    /** @var array{training?: array<string, mixed>, modules?: array<int, array<string, mixed>>, questions?: array<int, array<string, mixed>>} */
    #[Locked]
    public array $summary = [];

    public function boot(): void
    {
        abort_unless($this->participants()->isTrainingAvailable(), 404);
    }

    public function mount(): void
    {
        $this->email = (string) (auth()->user()?->email ?? '');
    }

    /**
     * @return array<int, array{id: string, name: string, code: string|null}>
     */
    #[Computed]
    public function tenants(): array
    {
        return $this->participants()->getTrainingTenants();
    }

    /**
     * Questions grouped by module, in the order the backend returned them.
     *
     * @return array<int, array{module: string, label: string, questions: array<int, array<string, mixed>>}>
     */
    #[Computed]
    public function modules(): array
    {
        return collect($this->questions)
            ->groupBy('module')
            ->map(fn ($questions, string $module): array => [
                'module' => $module,
                'label' => (string) ($questions->first()['module_label'] ?? Str::headline($module)),
                'questions' => $questions->values()->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array{module: string, label: string, questions: array<int, array<string, mixed>>}|null
     */
    #[Computed]
    public function currentModule(): ?array
    {
        return $this->modules()[$this->moduleIndex] ?? null;
    }

    public function answeredCount(): int
    {
        return collect($this->questions)->filter(fn (array $question): bool => $this->selectedOption($question['id']) !== null)->count();
    }

    public function isModuleComplete(int $index): bool
    {
        return collect($this->modules()[$index]['questions'] ?? [])
            ->every(fn (array $question): bool => $this->selectedOption($question['id']) !== null);
    }

    public function isLastModule(): bool
    {
        return $this->moduleIndex >= count($this->modules()) - 1;
    }

    public function requestKey(): void
    {
        $tenantIds = array_column($this->tenants(), 'id');
        $this->email = Str::lower(trim($this->email));

        $this->validate([
            'email' => ['required', 'email', 'max:255'],
            'tenantId' => ['required', 'string', 'in:'.implode(',', $tenantIds)],
        ], attributes: [
            'email' => __('expert-statistics::pbx.training.email'),
            'tenantId' => __('expert-statistics::pbx.training.tenant'),
        ]);

        $email = $this->email;
        $limiterKey = 'expert-statistics-training:'.sha1($email);

        if (RateLimiter::tooManyAttempts($limiterKey, self::MAX_KEY_REQUESTS)) {
            $this->addError('email', __('expert-statistics::pbx.training.too_many_requests', [
                'minutes' => (int) ceil(RateLimiter::availableIn($limiterKey) / 60),
            ]));

            return;
        }

        RateLimiter::hit($limiterKey, self::KEY_REQUEST_DECAY_SECONDS);

        if (! $this->participants()->isInvitedToTenant($this->tenantId, $email)) {
            $this->addError('email', __('expert-statistics::pbx.training.not_invited'));

            return;
        }

        $tenant = collect($this->tenants())->firstWhere('id', $this->tenantId);

        try {
            $this->service()->requestTrainingKey([
                'email' => $email,
                'tenant_id' => $this->tenantId,
                'tenant_name' => $tenant['name'] ?? null,
                'tenant_code' => $tenant['code'] ?? null,
                'locale' => app()->getLocale(),
                'training_url' => Route::has('expert-stats.training') ? route('expert-stats.training') : null,
            ]);
        } catch (Throwable $exception) {
            report($exception);
            $this->addError('email', __('expert-statistics::pbx.training.request_error'));

            return;
        }

        $this->accessKey = '';
        $this->step = self::STEP_KEY;

        Notification::make()
            ->title(__('expert-statistics::pbx.training.key_sent', ['email' => $email]))
            ->success()
            ->send();
    }

    public function showKeyForm(): void
    {
        $this->resetErrorBag();
        $this->step = self::STEP_KEY;
    }

    public function showRequestForm(): void
    {
        $this->resetErrorBag();
        $this->step = self::STEP_REQUEST;
    }

    public function startTraining(): void
    {
        $this->email = Str::lower(trim($this->email));

        $this->validate([
            'email' => ['required', 'email', 'max:255'],
            'accessKey' => ['required', 'string', 'max:32'],
        ], attributes: [
            'email' => __('expert-statistics::pbx.training.email'),
            'accessKey' => __('expert-statistics::pbx.training.key'),
        ]);

        $email = $this->email;
        $key = Str::upper(trim($this->accessKey));

        try {
            $data = $this->service()->startTraining(['email' => $email, 'key' => $key, 'locale' => app()->getLocale()]);
        } catch (InvalidTrainingKeyException $exception) {
            $this->addError('accessKey', __('expert-statistics::pbx.training.'.($exception->expired ? 'key_expired' : 'key_invalid')));

            return;
        } catch (Throwable $exception) {
            report($exception);
            $this->addError('accessKey', __('expert-statistics::pbx.training.generic_error'));

            return;
        }

        $this->trainingEmail = $email;
        $this->trainingKey = $key;

        // A completed training returns its summary straight away.
        if (isset($data['modules'])) {
            $this->showSummary($data);

            return;
        }

        $this->training = $data['training'] ?? [];
        $this->questions = $data['questions'] ?? [];
        $this->answers = collect($data['answers'] ?? [])
            ->mapWithKeys(fn ($optionId, $questionId): array => [(int) $questionId => (int) $optionId])
            ->all();
        $this->resetComputedState();

        $firstIncomplete = collect(array_keys($this->modules()))->first(fn (int $index): bool => ! $this->isModuleComplete($index));
        $this->moduleIndex = $firstIncomplete ?? max(count($this->modules()) - 1, 0);
        $this->step = self::STEP_QUIZ;
    }

    public function nextModule(): void
    {
        if (! $this->validateCurrentModule() || ! $this->saveCurrentModule()) {
            return;
        }

        $this->moduleIndex = min($this->moduleIndex + 1, count($this->modules()) - 1);
        $this->resetComputedState();
    }

    public function previousModule(): void
    {
        $this->resetErrorBag();
        $this->moduleIndex = max($this->moduleIndex - 1, 0);
        $this->resetComputedState();
    }

    public function finish(): void
    {
        if (! $this->validateCurrentModule() || ! $this->saveCurrentModule()) {
            return;
        }

        $incomplete = collect(array_keys($this->modules()))->first(fn (int $index): bool => ! $this->isModuleComplete($index));
        if ($incomplete !== null) {
            $this->moduleIndex = $incomplete;
            $this->resetComputedState();
            $this->validateCurrentModule();

            return;
        }

        try {
            $summary = $this->service()->completeTraining($this->credentials());
        } catch (InvalidTrainingKeyException $exception) {
            $this->expireSession($exception);

            return;
        } catch (Throwable $exception) {
            report($exception);
            $this->addError('quiz', __('expert-statistics::pbx.training.generic_error'));

            return;
        }

        $this->showSummary($summary);

        Notification::make()
            ->title(__('expert-statistics::pbx.training.certificate_sent', ['email' => $this->trainingEmail]))
            ->success()
            ->send();
    }

    public function restart(): void
    {
        $email = $this->trainingEmail ?? $this->email;

        $this->reset(['accessKey', 'tenantId', 'trainingEmail', 'trainingKey', 'training', 'questions', 'answers', 'moduleIndex', 'summary']);
        $this->resetErrorBag();
        $this->resetComputedState();
        $this->email = (string) $email;
        $this->step = self::STEP_REQUEST;
    }

    public function selectedOption(int|string $questionId): ?int
    {
        $optionId = $this->answers[$questionId] ?? null;

        return $optionId === null || $optionId === '' ? null : (int) $optionId;
    }

    public function render(): View
    {
        return view('expert-statistics::livewire.training.training');
    }

    private function validateCurrentModule(): bool
    {
        $this->resetErrorBag();

        foreach ($this->currentModule()['questions'] ?? [] as $question) {
            if ($this->selectedOption($question['id']) === null) {
                $this->addError('answers.'.$question['id'], __('expert-statistics::pbx.training.answer_required'));
            }
        }

        return $this->getErrorBag()->isEmpty();
    }

    private function saveCurrentModule(): bool
    {
        $answers = collect($this->currentModule()['questions'] ?? [])
            ->map(fn (array $question): array => ['question_id' => (int) $question['id'], 'option_id' => (int) $this->selectedOption($question['id'])])
            ->values()
            ->all();

        if ($answers === []) {
            return true;
        }

        try {
            $this->service()->saveTrainingAnswers([...$this->credentials(), 'answers' => $answers]);
        } catch (InvalidTrainingKeyException $exception) {
            $this->expireSession($exception);

            return false;
        } catch (Throwable $exception) {
            report($exception);
            $this->addError('quiz', __('expert-statistics::pbx.training.save_error'));

            return false;
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $summary
     */
    private function showSummary(array $summary): void
    {
        $this->summary = $summary;
        $this->training = $summary['training'] ?? [];
        $this->step = self::STEP_SUMMARY;
    }

    /**
     * The key expired (or was invalidated) mid-training: back to the key
     * form, with the reason on the key field.
     */
    private function expireSession(InvalidTrainingKeyException $exception): void
    {
        $this->step = self::STEP_KEY;
        $this->addError('accessKey', __('expert-statistics::pbx.training.'.($exception->expired ? 'key_expired' : 'key_invalid')));
    }

    /**
     * @return array{email: string, key: string}
     */
    private function credentials(): array
    {
        return ['email' => (string) $this->trainingEmail, 'key' => (string) $this->trainingKey];
    }

    private function resetComputedState(): void
    {
        unset($this->modules, $this->currentModule);
    }

    private function participants(): ProvidesTrainingParticipants
    {
        return app(ProvidesTrainingParticipants::class);
    }

    private function service(): ExpertStatisticsService
    {
        return app(ExpertStatisticsService::class);
    }
}
