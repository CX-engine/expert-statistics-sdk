<?php

declare(strict_types=1);

namespace CXEngine\ExpertStatistics\Contracts;

use CXEngine\ExpertStatistics\Support\DocsAssistantAnswer;

/**
 * A single "how do I use this / where do I find that" turn for the docs
 * assistant (Livewire\Docs\DocsAssistantChat). Implementations must only
 * ever be grounded in the Markdown documentation shipped in
 * resources/docs/user/ (see Support\DocsSearch / Support\DocsContent) -
 * never in ExpertStatisticsService or any other call-data source. That's
 * an architectural guarantee, not a prompt instruction: the default
 * implementation (Services\PrismDocsAssistantResponder) is never given a
 * reference to ExpertStatisticsService in the first place.
 *
 * Bound to Services\PrismDocsAssistantResponder in
 * ExpertStatisticsServiceProvider; a host app can rebind this to swap the
 * LLM provider/prompting strategy without touching the Livewire component.
 */
interface AnswersDocsQuestions
{
    /**
     * @param  array<int, array{role: 'user'|'assistant', content: string}>  $history  prior turns of the SAME conversation, oldest first - never persisted by the caller, kept only in the Livewire component's in-memory state for the duration of the page session
     */
    public function answer(string $question, string $locale, array $history = []): DocsAssistantAnswer;
}
