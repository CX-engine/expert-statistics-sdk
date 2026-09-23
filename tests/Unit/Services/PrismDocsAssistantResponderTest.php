<?php

use CXEngine\ExpertStatistics\Contracts\AnswersDocsQuestions;
use CXEngine\ExpertStatistics\Services\PrismDocsAssistantResponder;
use CXEngine\ExpertStatistics\Tests\TestCase;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\StructuredResponseFake;

uses(TestCase::class);

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

it('never receives an ExpertStatisticsService, by construction', function () {
    // Static guarantee: the class has no constructor parameters at all, so
    // there is no way for the service container - or anyone else - to hand
    // it a data-fetching dependency.
    $constructor = (new ReflectionClass(PrismDocsAssistantResponder::class))->getConstructor();

    expect($constructor)->toBeNull();
});
