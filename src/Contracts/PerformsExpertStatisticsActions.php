<?php

declare(strict_types=1);

namespace CXEngine\ExpertStatistics\Contracts;

use CXEngine\ExpertStatistics\Support\ActionAssistantTurn;

/**
 * The action-capable "Ask AI" assistant: can explain how to use Expert
 * Statistics (same grounding as Contracts\AnswersDocsQuestions) AND propose
 * (never execute) creating/scheduling a report or creating a resource
 * group. Execution is a separate, deterministic step entirely outside the
 * model's control — see Livewire\Docs\DocsAssistantChat::confirmAction(),
 * which validates then creates via ExpertStatisticsService only after an
 * explicit human Confirm click.
 *
 * Bound to Services\PrismActionAssistantResponder in
 * ExpertStatisticsServiceProvider, only used by DocsAssistantChat when
 * config('expert-statistics-api.ai_actions.enabled') is true — otherwise
 * the chat keeps using the narrower, read-only AnswersDocsQuestions.
 */
interface PerformsExpertStatisticsActions
{
    /**
     * @param  array<int, array{role: 'user'|'assistant', content: string}>  $history  prior turns of the SAME conversation, oldest first - never persisted by the caller
     * @param  array{type: 'report'|'resource_group', parameters: array<string, mixed>, summary: string, missingFields: array<int, string>, readyToConfirm: bool}|null  $pendingAction  the proposal from the previous turn, if any - lets the model revise it (e.g. "make it weekly instead") rather than starting over
     */
    public function handle(string $message, string $locale, array $history, ?array $pendingAction): ActionAssistantTurn;
}
