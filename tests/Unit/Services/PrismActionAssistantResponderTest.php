<?php

use CXEngine\ExpertStatistics\Contracts\PerformsExpertStatisticsActions;
use CXEngine\ExpertStatistics\Services\PrismActionAssistantResponder;
use CXEngine\ExpertStatistics\Tests\TestCase;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\StructuredResponseFake;
use Prism\Prism\Testing\TextResponseFake;

uses(TestCase::class);

function fakeStructuredTurn(array $overrides = []): StructuredResponseFake
{
    return StructuredResponseFake::make()->withStructured(array_merge([
        'answer' => 'Here is the answer.',
        'suggested_section_id' => null,
        'pending_report' => null,
        'pending_resource_group' => null,
    ], $overrides));
}

it('is bound to PerformsExpertStatisticsActions by the service provider', function () {
    expect(app(PerformsExpertStatisticsActions::class))->toBeInstanceOf(PrismActionAssistantResponder::class);
});

it('performs the gather (text/tools) call then the finalize (structured) call, in order', function () {
    Prism::fake([
        TextResponseFake::make()->withText('No analytics needed.'),
        fakeStructuredTurn(),
    ]);

    $turn = app(PrismActionAssistantResponder::class)->handle('How do I create a resource group?', 'en', [], null);

    expect($turn->answer)->toBe('Here is the answer.')
        ->and($turn->pendingAction)->toBeNull();
});

it('parses a ready-to-confirm pending_report proposal', function () {
    Prism::fake([
        TextResponseFake::make()->withText(''),
        fakeStructuredTurn([
            'pending_report' => [
                'name' => 'Weekly queues',
                'report_type' => 'report',
                'element_type' => '4',
                'dns' => '100,101',
                'pbx3cx_host_resource_group_id' => null,
                'start' => '2026-01-01 00:00:00',
                'end' => '2026-01-07 23:59:59',
                'email' => 'ops@example.com',
                'instant' => false,
                'repeat' => true,
                'repeat_pattern' => 'week',
                'missing_fields' => [],
                'summary' => 'A weekly report for queues 100 and 101, sent to ops@example.com.',
                'ready_to_confirm' => true,
            ],
        ]),
    ]);

    $turn = app(PrismActionAssistantResponder::class)->handle('Create a weekly report for queues 100 and 101', 'en', [], null);

    expect($turn->pendingAction)->not->toBeNull()
        ->and($turn->pendingAction['type'])->toBe('report')
        ->and($turn->pendingAction['readyToConfirm'])->toBeTrue()
        ->and($turn->pendingAction['parameters']['dns'])->toBe('100,101')
        ->and($turn->pendingAction['parameters']['repeat_pattern'])->toBe('week');
});

it('casts a numeric pbx3cx_host_resource_group_id to int, and a missing one to null', function () {
    Prism::fake([
        TextResponseFake::make()->withText(''),
        fakeStructuredTurn([
            'pending_report' => [
                'name' => 'Grouped report',
                'report_type' => 'report',
                'element_type' => '4',
                'dns' => null,
                'pbx3cx_host_resource_group_id' => '42',
                'start' => '2026-01-01', 'end' => '2026-01-07',
                'email' => 'a@example.com',
                'instant' => true, 'repeat' => false, 'repeat_pattern' => null,
                'missing_fields' => [], 'summary' => 'x', 'ready_to_confirm' => true,
            ],
        ]),
    ]);

    $turn = app(PrismActionAssistantResponder::class)->handle('x', 'en', [], null);

    expect($turn->pendingAction['parameters']['pbx3cx_host_resource_group_id'])->toBe(42);
});

it('maps the resource group type enum to the backend\'s integer codes', function (string $enumType, int $expectedInt) {
    Prism::fake([
        TextResponseFake::make()->withText(''),
        fakeStructuredTurn([
            'pending_resource_group' => [
                'name' => 'A group',
                'type' => $enumType,
                'members' => ['100'],
                'missing_fields' => [],
                'summary' => 'A group named "A group".',
                'ready_to_confirm' => true,
            ],
        ]),
    ]);

    $turn = app(PrismActionAssistantResponder::class)->handle('Create a group', 'en', [], null);

    expect($turn->pendingAction['parameters']['type'])->toBe($expectedInt)
        ->and($turn->pendingAction['parameters']['resources'])->toBe(['100']);
})->with([
    ['extension', 0],
    ['did', 1],
    ['queue', 4],
    ['caller', 99],
]);

it('discards a suggested_section_id the model hallucinated outside the known catalog', function () {
    Prism::fake([
        TextResponseFake::make()->withText(''),
        fakeStructuredTurn(['suggested_section_id' => 'not-a-real-section']),
    ]);

    $turn = app(PrismActionAssistantResponder::class)->handle('Some question', 'en', [], null);

    expect($turn->suggestedSectionId)->toBeNull();
});

it('returns a graceful fallback when the model returns no structured data', function () {
    Prism::fake([
        TextResponseFake::make()->withText(''),
        StructuredResponseFake::make()->withStructured([]),
    ]);

    $turn = app(PrismActionAssistantResponder::class)->handle('Some question', 'en', [], null);

    expect($turn->answer)->toBe(__('expert-statistics::pbx.docs_assistant.error_message'));
});

it('returns a graceful fallback when the provider config is invalid (fails before any HTTP call)', function () {
    config(['expert-statistics-api.ai_actions.provider' => 'not-a-real-provider']);

    $turn = app(PrismActionAssistantResponder::class)->handle('Some question', 'en', [], null);

    expect($turn->answer)->toBe(__('expert-statistics::pbx.docs_assistant.error_message'))
        ->and($turn->pendingAction)->toBeNull();
});

it('passes the current pendingAction through to the finalize prompt, for revision', function () {
    $rendered = (string) view('expert-statistics::prompts.action-assistant-finalize-system', [
        'locale' => 'en',
        'knownSectionIds' => ['pbx-settings'],
        'dataRedirectSectionId' => 'ai-insights',
        'excerpts' => [],
        'reasoning' => '',
        'pendingAction' => [
            'type' => 'report',
            'parameters' => ['repeat_pattern' => 'week'],
            'summary' => 'x',
            'missingFields' => ['email'],
            'readyToConfirm' => false,
        ],
        'availableQueues' => [],
        'availableExtensions' => [],
        'reportTypes' => ['report'],
        'repeatPatterns' => ['week'],
        'resourceGroupTypes' => ['queue'],
    ]);

    expect($rendered)->toContain('IN-PROGRESS proposal')
        ->and($rendered)->toContain('email');
});

it('the finalize prompt states the exactly-two-skills scope limit', function () {
    $rendered = (string) view('expert-statistics::prompts.action-assistant-finalize-system', [
        'locale' => 'en',
        'knownSectionIds' => [],
        'dataRedirectSectionId' => 'ai-insights',
        'excerpts' => [],
        'reasoning' => '',
        'pendingAction' => null,
        'availableQueues' => [],
        'availableExtensions' => [],
        'reportTypes' => [],
        'repeatPatterns' => [],
        'resourceGroupTypes' => [],
    ]);

    expect($rendered)->toContain('EXACTLY two mutation skills');
});
