<?php

use CXEngine\ExpertStatistics\Exceptions\AiFeaturesNotActivatedException;
use CXEngine\ExpertStatistics\Livewire\Docs\DocsAssistantChat;
use CXEngine\ExpertStatistics\Services\ExpertStatisticsService;
use CXEngine\ExpertStatistics\Tests\TestCase;
use Illuminate\Foundation\Auth\User as GenericUser;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\StructuredResponseFake;
use Prism\Prism\Testing\TextResponseFake;

uses(TestCase::class);

function userWithModifyPermission(bool $canModify): GenericUser
{
    Gate::define('expert-statistics.modify', fn () => $canModify);

    $user = new GenericUser;
    $user->id = $canModify ? 1 : 2;

    return $user;
}

function samplePendingReport(): array
{
    return [
        'type' => 'report',
        'parameters' => [
            'name' => 'Weekly queues', 'report_type' => 'report', 'element_type' => '4',
            'dns' => '100,101', 'pbx3cx_host_resource_group_id' => null,
            'start' => '2026-01-01 00:00:00', 'end' => '2026-01-07 23:59:59',
            'email' => 'ops@example.com', 'instant' => false, 'repeat' => true, 'repeat_pattern' => 'week',
        ],
        'summary' => 'A weekly report for queues 100/101, sent to ops@example.com.',
        'missingFields' => [],
        'readyToConfirm' => true,
    ];
}

function samplePendingGroup(): array
{
    return [
        'type' => 'resource_group',
        'parameters' => ['name' => 'Busiest queues', 'type' => 4, 'resources' => ['100', '101']],
        'summary' => 'A group "Busiest queues" with queues 100 and 101.',
        'missingFields' => [],
        'readyToConfirm' => true,
    ];
}

function fakeAnswer(?string $sectionId = null): StructuredResponseFake
{
    return StructuredResponseFake::make()->withStructured([
        'answer' => 'Here is how you do it.',
        'suggested_section_id' => $sectionId,
    ]);
}

it('starts closed with no messages', function () {
    Livewire::test(DocsAssistantChat::class)
        ->assertSet('open', false)
        ->assertSet('messages', []);
});

it('opens and closes independently of the Documentation panel', function () {
    Livewire::test(DocsAssistantChat::class)
        ->call('openPanel')
        ->assertSet('open', true)
        ->call('closePanel')
        ->assertSet('open', false);
});

it('does nothing for a blank question', function () {
    Livewire::test(DocsAssistantChat::class)
        ->set('question', '   ')
        ->call('ask')
        ->assertSet('messages', []);
});

it('appends a user turn and an assistant turn, then clears the input', function () {
    Prism::fake([fakeAnswer('pbx-settings')]);

    Livewire::test(DocsAssistantChat::class)
        ->set('question', 'How do I create a resource group?')
        ->call('ask')
        ->assertSet('question', '')
        ->assertSet('messages.0.role', 'user')
        ->assertSet('messages.0.content', 'How do I create a resource group?')
        ->assertSet('messages.1.role', 'assistant')
        ->assertSet('messages.1.content', 'Here is how you do it.')
        ->assertSet('messages.1.suggestedSectionId', 'pbx-settings');
});

it('clears the whole conversation on demand, with nothing left behind', function () {
    Prism::fake([fakeAnswer(), fakeAnswer()]);

    Livewire::test(DocsAssistantChat::class)
        ->set('question', 'First question')
        ->call('ask')
        ->call('clearConversation')
        ->assertSet('messages', []);
});

it('a starter suggestion asks its question immediately', function () {
    Prism::fake([fakeAnswer()]);

    Livewire::test(DocsAssistantChat::class)
        ->call('useSuggestion', 'How do I schedule a report?')
        ->assertSet('messages.0.content', 'How do I schedule a report?');
});

describe('sendMessage()/getResponse() split (so the user turn renders before the reply)', function () {
    it('sendMessage appends only the user turn, sets thinking, and never calls the model', function () {
        Prism::fake([]);

        Livewire::test(DocsAssistantChat::class)
            ->set('question', 'How do I create a resource group?')
            ->call('sendMessage')
            ->assertSet('question', '')
            ->assertSet('thinking', true)
            ->assertSet('messages', [
                ['role' => 'user', 'content' => 'How do I create a resource group?'],
            ]);
    });

    it('sendMessage does nothing for a blank question', function () {
        Livewire::test(DocsAssistantChat::class)
            ->set('question', '   ')
            ->call('sendMessage')
            ->assertSet('thinking', false)
            ->assertSet('messages', []);
    });

    it('getResponse answers the pending user turn and clears thinking', function () {
        Prism::fake([fakeAnswer('pbx-settings')]);

        Livewire::test(DocsAssistantChat::class)
            ->set('question', 'How do I create a resource group?')
            ->call('sendMessage')
            ->call('getResponse')
            ->assertSet('thinking', false)
            ->assertSet('messages.1.role', 'assistant')
            ->assertSet('messages.1.content', 'Here is how you do it.')
            ->assertSet('messages.1.suggestedSectionId', 'pbx-settings');
    });

    it('getResponse is a no-op when there is nothing pending', function () {
        Prism::fake([]);

        Livewire::test(DocsAssistantChat::class)
            ->call('getResponse')
            ->assertSet('messages', []);
    });
});

it('end-to-end: a data question redirects to the real AI Chat page when clicked', function () {
    Route::get('/expert-stats/ai/chat', fn () => '')->name('expert-stats.ai.chat');

    Prism::fake([
        fakeAnswer('ai-insights'), // simulates the model following the "redirect data questions" rule
    ]);

    Livewire::test(DocsAssistantChat::class)
        ->set('question', 'How many calls did we lose last week?')
        ->call('ask')
        ->assertSet('messages.1.suggestedSectionId', 'ai-insights')
        ->call('goToSuggestion', 'ai-insights')
        ->assertRedirect(route('expert-stats.ai.chat'));
});

describe('suggestedUrl / goToSuggestion', function () {
    it('returns null for a section with no associated app route', function () {
        // 'permissions' is a documentation-only section - see DocsCatalog.
        $url = Livewire::test(DocsAssistantChat::class)->instance()->suggestedUrl('permissions');

        expect($url)->toBeNull();
    });

    it('returns a real URL for a section whose route is registered', function () {
        Route::get('/expert-stats/dashboard', fn () => '')->name('expert-stats.dashboard');

        $url = Livewire::test(DocsAssistantChat::class)->instance()->suggestedUrl('dashboard');

        expect($url)->toBe(route('expert-stats.dashboard'));
    });

    it('returns null when the section\'s route isn\'t registered in this app', function () {
        // 'dashboard' maps to route name expert-stats.dashboard in
        // DocsCatalog, but nothing registers it in this Testbench app.
        $url = Livewire::test(DocsAssistantChat::class)->instance()->suggestedUrl('dashboard');

        expect($url)->toBeNull();
    });

    it('redirects when the suggested section has a real route', function () {
        Route::get('/expert-stats/dashboard', fn () => '')->name('expert-stats.dashboard');

        Livewire::test(DocsAssistantChat::class)
            ->call('goToSuggestion', 'dashboard')
            ->assertRedirect(route('expert-stats.dashboard'));
    });

    it('closes itself and asks the Documentation panel to open on the section, when there is no route to link to', function () {
        Livewire::test(DocsAssistantChat::class)
            ->call('openPanel')
            ->call('goToSuggestion', 'permissions')
            ->assertSet('open', false)
            ->assertDispatched('docs-assistant.show-section', sectionId: 'permissions');
    });

    it('does nothing when asked to go to a null suggestion', function () {
        Livewire::test(DocsAssistantChat::class)
            ->call('goToSuggestion', null)
            ->assertNotDispatched('docs-assistant.show-section')
            ->assertNoRedirect();
    });
});

describe('explainCapabilities', function () {
    it('appends the plain Q&A capabilities message when actions are disabled', function () {
        config(['expert-statistics-api.ai_actions.enabled' => false]);

        Livewire::test(DocsAssistantChat::class)
            ->call('explainCapabilities')
            ->assertSet('messages.0.content', __('expert-statistics::pbx.docs_assistant.capabilities_message'));
    });

    it('appends the actions-aware capabilities message when actions are enabled', function () {
        config(['expert-statistics-api.ai_actions.enabled' => true]);

        Livewire::test(DocsAssistantChat::class)
            ->call('explainCapabilities')
            ->assertSet('messages.0.content', __('expert-statistics::pbx.docs_assistant.capabilities_message_with_actions'));
    });
});

describe('ask() routing by ai_actions.enabled', function () {
    it('still uses the plain read-only responder when actions are disabled (default)', function () {
        config(['expert-statistics-api.ai_actions.enabled' => false]);
        Prism::fake([fakeAnswer()]);

        Livewire::test(DocsAssistantChat::class)
            ->set('question', 'How do I create a resource group?')
            ->call('ask')
            ->assertSet('pendingAction', null);
    });

    it('routes to the action-capable responder and stores its pendingAction when enabled', function () {
        config(['expert-statistics-api.ai_actions.enabled' => true]);

        Prism::fake([
            TextResponseFake::make()->withText(''),
            StructuredResponseFake::make()->withStructured([
                'answer' => 'Here is the proposal.',
                'suggested_section_id' => null,
                'pending_report' => null,
                'pending_resource_group' => [
                    'name' => 'Busiest queues', 'type' => 'queue', 'members' => ['100', '101'],
                    'missing_fields' => [], 'summary' => 'A group with queues 100 and 101.',
                    'ready_to_confirm' => true,
                ],
            ]),
        ]);

        Livewire::test(DocsAssistantChat::class)
            ->set('question', 'Create a group with queues 100 and 101')
            ->call('ask')
            ->assertSet('messages.1.content', 'Here is the proposal.')
            ->assertSet('pendingAction.type', 'resource_group')
            ->assertSet('pendingAction.readyToConfirm', true);
    });
});

describe('confirmAction', function () {
    it('does nothing when there is no pending action', function () {
        Livewire::test(DocsAssistantChat::class)
            ->call('confirmAction')
            ->assertSet('messages', []);
    });

    it('declines gracefully, without calling the backend, when the user lacks modify permission', function () {
        test()->actingAs(userWithModifyPermission(false));

        $this->mock(ExpertStatisticsService::class, function ($mock) {
            $mock->shouldNotReceive('validateAiHelperReport');
            $mock->shouldNotReceive('createAiHelperReport');
        });

        Livewire::test(DocsAssistantChat::class)
            ->set('pendingAction', samplePendingReport())
            ->call('confirmAction')
            ->assertSet('messages.0.content', __('expert-statistics::pbx.docs_assistant.action_permission_denied'))
            ->assertSet('pendingAction', null);
    });

    it('keeps the pending action and shows errors when validation fails — never calls create', function () {
        test()->actingAs(userWithModifyPermission(true));

        $this->mock(ExpertStatisticsService::class, function ($mock) {
            $mock->shouldReceive('validateAiHelperReport')->once()->andReturn([
                'valid' => false,
                'errors' => ['email' => ['The email field is required.']],
            ]);
            $mock->shouldNotReceive('createAiHelperReport');
        });

        Livewire::test(DocsAssistantChat::class)
            ->set('pendingAction', samplePendingReport())
            ->call('confirmAction')
            ->assertSet('messages.0.content', __('expert-statistics::pbx.docs_assistant.action_validation_failed', [
                'errors' => 'The email field is required.',
            ]))
            ->assertSet('pendingAction.type', 'report');
    });

    it('validates then creates a report, in that order, and clears the pending action on success', function () {
        test()->actingAs(userWithModifyPermission(true));

        $this->mock(ExpertStatisticsService::class, function ($mock) {
            $mock->shouldReceive('validateAiHelperReport')->once()->ordered()->andReturn(['valid' => true, 'errors' => []]);
            $mock->shouldReceive('createAiHelperReport')->once()->ordered()->andReturn(['id' => 'abc']);
        });

        Livewire::test(DocsAssistantChat::class)
            ->set('pendingAction', samplePendingReport())
            ->call('confirmAction')
            ->assertSet('messages.0.content', __('expert-statistics::pbx.docs_assistant.action_report_created'))
            ->assertSet('pendingAction', null);
    });

    it('validates then creates a resource group, in that order, and clears the pending action on success', function () {
        test()->actingAs(userWithModifyPermission(true));

        $this->mock(ExpertStatisticsService::class, function ($mock) {
            $mock->shouldReceive('validateAiHelperResourceGroup')->once()->ordered()->andReturn(['valid' => true, 'errors' => []]);
            $mock->shouldReceive('createAiHelperResourceGroup')->once()->ordered()->andReturn(['id' => 1]);
        });

        Livewire::test(DocsAssistantChat::class)
            ->set('pendingAction', samplePendingGroup())
            ->call('confirmAction')
            ->assertSet('messages.0.content', __('expert-statistics::pbx.docs_assistant.action_group_created'))
            ->assertSet('pendingAction', null);
    });

    it('shows a friendly message and clears the pending action when the host has no AI features activated', function () {
        test()->actingAs(userWithModifyPermission(true));

        $this->mock(ExpertStatisticsService::class, function ($mock) {
            $mock->shouldReceive('validateAiHelperReport')->once()->andReturn(['valid' => true, 'errors' => []]);
            $mock->shouldReceive('createAiHelperReport')->once()->andThrow(AiFeaturesNotActivatedException::make());
        });

        Livewire::test(DocsAssistantChat::class)
            ->set('pendingAction', samplePendingReport())
            ->call('confirmAction')
            ->assertSet('messages.0.content', __('expert-statistics::pbx.docs_assistant.action_not_activated'))
            ->assertSet('pendingAction', null);
    });

    it('keeps the pending action (so the user can retry) on an unexpected error', function () {
        test()->actingAs(userWithModifyPermission(true));

        $this->mock(ExpertStatisticsService::class, function ($mock) {
            $mock->shouldReceive('validateAiHelperReport')->once()->andReturn(['valid' => true, 'errors' => []]);
            $mock->shouldReceive('createAiHelperReport')->once()->andThrow(new RuntimeException('network blip'));
        });

        Livewire::test(DocsAssistantChat::class)
            ->set('pendingAction', samplePendingReport())
            ->call('confirmAction')
            ->assertSet('messages.0.content', __('expert-statistics::pbx.docs_assistant.action_error'))
            ->assertSet('pendingAction.type', 'report');
    });
});

describe('cancelAction', function () {
    it('does nothing when there is no pending action', function () {
        Livewire::test(DocsAssistantChat::class)
            ->call('cancelAction')
            ->assertSet('messages', []);
    });

    it('clears the pending action and acknowledges the cancellation', function () {
        Livewire::test(DocsAssistantChat::class)
            ->set('pendingAction', samplePendingReport())
            ->call('cancelAction')
            ->assertSet('pendingAction', null)
            ->assertSet('messages.0.content', __('expert-statistics::pbx.docs_assistant.action_cancelled'));
    });
});
