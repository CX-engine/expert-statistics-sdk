<?php

use CXEngine\ExpertStatistics\Contracts\ProvidesTrainingParticipants;
use CXEngine\ExpertStatistics\Exceptions\InvalidTrainingKeyException;
use CXEngine\ExpertStatistics\Livewire\Training\Training;
use CXEngine\ExpertStatistics\Services\ExpertStatisticsService;
use CXEngine\ExpertStatistics\Tests\Doubles\FakeTrainingParticipants;
use CXEngine\ExpertStatistics\Tests\TestCase;
use Illuminate\Foundation\Auth\User as GenericUser;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

uses(TestCase::class);

/**
 * @return array{training: array<string, mixed>, questions: array<int, array<string, mixed>>, answers: array<int, int>}
 */
function trainingStartPayload(array $answers = []): array
{
    $question = fn (int $id, string $module, string $label): array => [
        'id' => $id,
        'module' => $module,
        'module_label' => $label,
        'position' => $id,
        'question' => "Question {$id}?",
        'options' => [
            ['id' => $id * 10 + 1, 'label' => "Option {$id}.1"],
            ['id' => $id * 10 + 2, 'label' => "Option {$id}.2"],
        ],
    ];

    return [
        'training' => ['id' => 1, 'email' => 'jane@example.com', 'status' => 'in_progress'],
        'questions' => [
            $question(1, 'dashboard', 'Dashboard'),
            $question(2, 'dashboard', 'Dashboard'),
            $question(3, 'reports', 'Reports'),
        ],
        'answers' => $answers,
    ];
}

/**
 * @return array<string, mixed>
 */
function trainingSummaryPayload(): array
{
    return [
        'training' => ['email' => 'jane@example.com', 'score' => 66.67, 'correct_answers' => 2, 'total_questions' => 3, 'passed' => false, 'pass_score' => 70],
        'modules' => [
            ['module' => 'dashboard', 'label' => 'Dashboard', 'total' => 2, 'correct' => 2, 'score' => 100],
            ['module' => 'reports', 'label' => 'Reports', 'total' => 1, 'correct' => 0, 'score' => 0],
        ],
        'questions' => [
            [
                'id' => 3, 'module' => 'reports', 'module_label' => 'Reports', 'question' => 'Question 3?',
                'explanation' => 'Because.', 'selected_option_id' => 31, 'correct_option_id' => 32, 'is_correct' => false,
                'options' => [['id' => 31, 'label' => 'Option 3.1', 'is_correct' => false], ['id' => 32, 'label' => 'Option 3.2', 'is_correct' => true]],
            ],
        ],
    ];
}

beforeEach(function () {
    Blade::anonymousComponentPath(__DIR__.'/../../../Doubles/views/components');
    Route::get('/expert-stats/training', Training::class)->name('expert-stats.training');
    RateLimiter::clear('expert-statistics-training:'.sha1('jane@example.com'));

    Gate::define('expert-statistics.view', fn () => true);
    $user = new GenericUser;
    $user->id = 1;
    $user->email = 'jane@example.com';
    $this->actingAs($user);

    $this->app->instance(ProvidesTrainingParticipants::class, new FakeTrainingParticipants);
});

it('is not found when the host app does not offer the training', function () {
    $this->app->instance(ProvidesTrainingParticipants::class, new FakeTrainingParticipants(available: false));

    Livewire::test(Training::class)->assertNotFound();
});

it('prefills the email and lists the tenants', function () {
    Livewire::test(Training::class)
        ->assertSet('step', Training::STEP_REQUEST)
        ->assertSet('email', 'jane@example.com')
        ->assertSee('Acme')
        ->assertSee('Globex');
});

describe('requesting a key', function () {
    it('refuses an email that was not invited to the tenant', function () {
        $this->mock(ExpertStatisticsService::class)->shouldNotReceive('requestTrainingKey');

        Livewire::test(Training::class)
            ->set('tenantId', 'tenant-2')
            ->call('requestKey')
            ->assertHasErrors('email')
            ->assertSet('step', Training::STEP_REQUEST);
    });

    it('validates the tenant against the available list', function () {
        $this->mock(ExpertStatisticsService::class)->shouldNotReceive('requestTrainingKey');

        Livewire::test(Training::class)
            ->set('tenantId', 'unknown')
            ->call('requestKey')
            ->assertHasErrors(['tenantId' => 'in']);
    });

    it('has the backend email a key to an invited participant', function () {
        $this->mock(ExpertStatisticsService::class)
            ->shouldReceive('requestTrainingKey')
            ->once()
            ->withArgs(fn (array $data) => $data['email'] === 'jane@example.com'
                && $data['tenant_id'] === 'tenant-1'
                && $data['tenant_name'] === 'Acme'
                && $data['tenant_code'] === '0001'
                && str_ends_with((string) $data['training_url'], '/expert-stats/training'))
            ->andReturn(['id' => 1]);

        Livewire::test(Training::class)
            ->set('email', ' Jane@Example.com ')
            ->set('tenantId', 'tenant-1')
            ->call('requestKey')
            ->assertHasNoErrors()
            ->assertSet('step', Training::STEP_KEY)
            ->assertSet('email', 'jane@example.com');
    });

    it('throttles repeated requests for the same email', function () {
        $this->mock(ExpertStatisticsService::class)->shouldReceive('requestTrainingKey')->times(3)->andReturn([]);

        $component = Livewire::test(Training::class)->set('tenantId', 'tenant-1');

        foreach (range(1, 3) as $attempt) {
            $component->call('requestKey')->assertHasNoErrors();
        }

        $component->call('requestKey')->assertHasErrors('email');
    });
});

describe('taking the training', function () {
    it('shows the key error returned by the backend', function () {
        $this->mock(ExpertStatisticsService::class)
            ->shouldReceive('startTraining')
            ->andThrow(InvalidTrainingKeyException::make(expired: true));

        Livewire::test(Training::class)
            ->call('showKeyForm')
            ->set('accessKey', 'AAAA-BBBB-CCCC')
            ->call('startTraining')
            ->assertHasErrors('accessKey')
            ->assertSee(__('expert-statistics::pbx.training.key_expired'))
            ->assertSet('step', Training::STEP_KEY);
    });

    it('starts the quiz and resumes at the first module with unanswered questions', function () {
        $this->mock(ExpertStatisticsService::class)
            ->shouldReceive('startTraining')
            ->once()
            ->with(['email' => 'jane@example.com', 'key' => 'AAAA-BBBB-CCCC', 'locale' => 'en'])
            ->andReturn(trainingStartPayload(answers: [1 => 11, 2 => 22]));

        Livewire::test(Training::class)
            ->call('showKeyForm')
            ->set('accessKey', 'aaaa-bbbb-cccc')
            ->call('startTraining')
            ->assertSet('step', Training::STEP_QUIZ)
            ->assertSet('moduleIndex', 1)
            ->assertSet('trainingKey', 'AAAA-BBBB-CCCC')
            ->assertSee('Question 3?')
            ->assertDontSee('Question 1?');
    });

    it('requires every question of a module to be answered, then saves them', function () {
        $service = $this->mock(ExpertStatisticsService::class);
        $service->shouldReceive('startTraining')->andReturn(trainingStartPayload());
        $service->shouldReceive('saveTrainingAnswers')
            ->once()
            ->with([
                'email' => 'jane@example.com',
                'key' => 'AAAA-BBBB-CCCC',
                'answers' => [['question_id' => 1, 'option_id' => 11], ['question_id' => 2, 'option_id' => 22]],
            ])
            ->andReturn([]);

        Livewire::test(Training::class)
            ->call('showKeyForm')
            ->set('accessKey', 'AAAA-BBBB-CCCC')
            ->call('startTraining')
            ->assertSet('moduleIndex', 0)
            ->set('answers.1', '11')
            ->call('nextModule')
            ->assertHasErrors('answers.2')
            ->assertSet('moduleIndex', 0)
            ->set('answers.2', '22')
            ->call('nextModule')
            ->assertHasNoErrors()
            ->assertSet('moduleIndex', 1)
            ->assertSee('Question 3?');
    });

    it('completes the training and shows the summary', function () {
        $service = $this->mock(ExpertStatisticsService::class);
        $service->shouldReceive('startTraining')->andReturn(trainingStartPayload(answers: [1 => 11, 2 => 22]));
        $service->shouldReceive('saveTrainingAnswers')->once()->andReturn([]);
        $service->shouldReceive('completeTraining')
            ->once()
            ->with(['email' => 'jane@example.com', 'key' => 'AAAA-BBBB-CCCC'])
            ->andReturn(trainingSummaryPayload());

        Livewire::test(Training::class)
            ->call('showKeyForm')
            ->set('accessKey', 'AAAA-BBBB-CCCC')
            ->call('startTraining')
            ->set('answers.3', '31')
            ->call('finish')
            ->assertSet('step', Training::STEP_SUMMARY)
            ->assertSee('66.67%')
            ->assertSee(__('expert-statistics::pbx.training.failed'))
            ->assertSee('Option 3.2')
            ->assertSee('Because.');
    });

    it('goes straight to the summary for an already completed training', function () {
        $this->mock(ExpertStatisticsService::class)->shouldReceive('startTraining')->andReturn(trainingSummaryPayload());

        Livewire::test(Training::class)
            ->call('showKeyForm')
            ->set('accessKey', 'AAAA-BBBB-CCCC')
            ->call('startTraining')
            ->assertSet('step', Training::STEP_SUMMARY)
            ->call('restart')
            ->assertSet('step', Training::STEP_REQUEST)
            ->assertSet('summary', []);
    });
});
