<?php

use CXEngine\ExpertStatistics\Contracts\AnswersDocsQuestions;
use CXEngine\ExpertStatistics\Services\PrismDocsAssistantResponder;
use CXEngine\ExpertStatistics\Tests\TestCase;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\StructuredResponseFake;

uses(TestCase::class);

function fakeStructured(string $answer, ?string $suggestedSectionId = null): StructuredResponseFake
{
    return StructuredResponseFake::make()->withStructured([
        'answer' => $answer,
        'suggested_section_id' => $suggestedSectionId,
    ]);
}

it('is bound to AnswersDocsQuestions by the service provider', function () {
    expect(app(AnswersDocsQuestions::class))->toBeInstanceOf(PrismDocsAssistantResponder::class);
});

it('returns the model\'s structured answer and suggested section', function () {
    Prism::fake([
        StructuredResponseFake::make()->withStructured([
            'answer' => 'Open PBX Settings, then the Groups tab, then "+ New group".',
            'suggested_section_id' => 'pbx-settings',
        ]),
    ]);

    $answer = app(PrismDocsAssistantResponder::class)->answer('How do I create a resource group?', 'en');

    expect($answer->answer)->toBe('Open PBX Settings, then the Groups tab, then "+ New group".')
        ->and($answer->suggestedSectionId)->toBe('pbx-settings')
        ->and($answer->sourceSectionIds)->not->toBeEmpty();
});

it('discards a suggested_section_id the model hallucinated outside the known catalog', function () {
    Prism::fake([
        StructuredResponseFake::make()->withStructured([
            'answer' => 'Some answer.',
            'suggested_section_id' => 'not-a-real-section',
        ]),
    ]);

    $answer = app(PrismDocsAssistantResponder::class)->answer('Some question', 'en');

    expect($answer->suggestedSectionId)->toBeNull();
});

it('returns a graceful fallback answer when the LLM call throws', function () {
    // Prism's fake only accepts canned responses, not a throwing closure -
    // force a real Throwable through the try/catch a different way: an
    // invalid provider name makes Provider::from() throw \ValueError
    // (a \Throwable) before any HTTP call would even happen.
    config(['expert-statistics-api.docs_assistant.provider' => 'not-a-real-provider']);

    $answer = app(PrismDocsAssistantResponder::class)->answer('How do I create a resource group?', 'en');

    expect($answer->answer)->toBe(__('expert-statistics::pbx.docs_assistant.error_message'))
        ->and($answer->suggestedSectionId)->toBeNull();
});

it('returns a graceful fallback when the model returns no structured data', function () {
    Prism::fake([
        StructuredResponseFake::make()->withStructured([]),
    ]);

    $answer = app(PrismDocsAssistantResponder::class)->answer('How do I create a resource group?', 'en');

    expect($answer->answer)->toBe(__('expert-statistics::pbx.docs_assistant.error_message'));
});

it('always includes the AI Chat (ai-insights) section in context, even for unrelated questions', function () {
    Prism::fake([fakeStructured('Some answer.')]);

    $answer = app(PrismDocsAssistantResponder::class)->answer('What is a resource group?', 'en');

    // "resource group" doesn't lexically match the AI Insights doc at all,
    // but it must still be force-included as grounding for the redirect
    // rule - see PrismDocsAssistantResponder::DATA_QUESTION_REDIRECT_SECTION_ID.
    expect($answer->sourceSectionIds)->toContain('ai-insights');
});

it('the system prompt explicitly instructs the model to redirect data questions to AI Chat', function () {
    $rendered = (string) view('expert-statistics::prompts.docs-assistant-system', [
        'locale' => 'en',
        'knownSectionIds' => ['ai-insights', 'pbx-settings'],
        'dataRedirectSectionId' => 'ai-insights',
        'excerpts' => [],
    ]);

    expect($rendered)->toContain('NEVER answer questions about the user\'s actual call data')
        ->and($rendered)->toContain('REDIRECT')
        ->and($rendered)->toContain('suggested_section_id to exactly "ai-insights"');
});

it('the schema tells the model the redirect is mandatory for data questions, not just a suggestion', function () {
    $responder = new PrismDocsAssistantResponder;
    $schema = (new ReflectionClass($responder))->getMethod('schema')->invoke($responder)->toArray();

    expect($schema['properties']['suggested_section_id']['description'])
        ->toContain('MUST be set to "ai-insights"');
});

it('respects a suggested_section_id of ai-insights returned by the model', function () {
    Prism::fake([
        StructuredResponseFake::make()->withStructured([
            'answer' => 'I can\'t see your call data. Ask the AI Chat page instead.',
            'suggested_section_id' => 'ai-insights',
        ]),
    ]);

    $answer = app(PrismDocsAssistantResponder::class)->answer('How many calls did we lose last week?', 'en');

    expect($answer->suggestedSectionId)->toBe('ai-insights');
});

it('never receives an ExpertStatisticsService, by construction', function () {
    // Static guarantee: the class has no constructor parameters at all, so
    // there is no way for the service container - or anyone else - to hand
    // it a data-fetching dependency.
    $constructor = (new ReflectionClass(PrismDocsAssistantResponder::class))->getConstructor();

    expect($constructor)->toBeNull();
});
