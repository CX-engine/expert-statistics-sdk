<?php

declare(strict_types=1);

namespace CXEngine\ExpertStatistics\Livewire\Docs;

use CXEngine\ExpertStatistics\Contracts\AnswersDocsQuestions;
use CXEngine\ExpertStatistics\Support\DocsCatalog;
use Illuminate\Support\Facades\Route;
use Livewire\Component;

/**
 * "Ask AI" floating chat bubble (bottom-right), a deliberately separate
 * floating button from the "Documentation" panel (Livewire\Docs\DocsHelperPanel,
 * bottom-left) rather than a tab inside it. Answers "how do I use this
 * feature" / "where do I find that" questions, grounded only in the
 * Markdown docs (Contracts\AnswersDocsQuestions => Services\PrismDocsAssistantResponder
 * by default).
 *
 * Coordinates with DocsHelperPanel one-way: when a suggested doc section has
 * no dedicated app route to link to, this dispatches 'docs-assistant.show-section'
 * so that panel opens itself on that section - see goToSuggestion().
 *
 * Three hard boundaries, by design, not by convention:
 * - No storage: $messages lives only in this component's own public state
 *   for the current page session. Nothing is written to the database, the
 *   cache, or the session. Reloading the page loses the conversation -
 *   that's intentional, not a bug.
 * - No stats/data access: this class never references ExpertStatisticsService
 *   and has no way to reach the XP-Stats API. It can only read the same
 *   Markdown files the Documentation panel itself renders.
 * - Read-only for now: it can only *talk about* creating a report/group or
 *   changing the active host, never actually do it. Performing those
 *   actions on the user's behalf is planned future work - see
 *   resources/docs/ai-helper-architecture-plan.md - and needs its own
 *   explicit tool-calling design (with modify-permission checks) before any
 *   code is added here, not a silent capability creep into this class.
 */
class DocsAssistantChat extends Component
{
    public bool $open = false;

    /**
     * @var array<int, array{role: 'user'|'assistant', content: string, suggestedSectionId?: string|null}>
     */
    public array $messages = [];

    public string $question = '';

    public function openPanel(): void
    {
        $this->open = true;
    }

    public function closePanel(): void
    {
        $this->open = false;
    }

    public function ask(): void
    {
        $question = trim($this->question);

        if ($question === '') {
            return;
        }

        $history = array_map(
            fn (array $turn) => ['role' => $turn['role'], 'content' => $turn['content']],
            $this->messages,
        );

        $this->messages[] = ['role' => 'user', 'content' => $question];
        $this->question = '';

        $answer = app(AnswersDocsQuestions::class)->answer($question, app()->getLocale(), $history);

        $this->messages[] = [
            'role' => 'assistant',
            'content' => $answer->answer,
            'suggestedSectionId' => $answer->suggestedSectionId,
        ];
    }

    public function useSuggestion(string $question): void
    {
        $this->question = $question;
        $this->ask();
    }

    public function clearConversation(): void
    {
        $this->messages = [];
    }

    public function sectionTitle(string $sectionId): string
    {
        return __('expert-statistics::pbx.docs.sections.'.$sectionId);
    }

    /**
     * A real app route to link to for this suggestion, if the section has
     * one - e.g. "my-queues" -> the actual My Queues report page. Null when
     * the section is documentation-only (e.g. the permissions/glossary/FAQ
     * pages have no single page of their own to link to).
     */
    public function suggestedUrl(?string $sectionId): ?string
    {
        if ($sectionId === null) {
            return null;
        }

        $routeName = DocsCatalog::find($sectionId)['routes'][0] ?? null;

        return $routeName !== null && Route::has($routeName) ? route($routeName) : null;
    }

    public function goToSuggestion(?string $sectionId): void
    {
        if ($sectionId === null) {
            return;
        }

        if ($url = $this->suggestedUrl($sectionId)) {
            $this->redirect($url);

            return;
        }

        // No dedicated page for this section - ask the Documentation panel
        // (a separate component/bubble) to open itself on it instead.
        $this->open = false;
        $this->dispatch('docs-assistant.show-section', sectionId: $sectionId);
    }

    /**
     * @return array<int, string>
     */
    public function starterSuggestions(): array
    {
        return __('expert-statistics::pbx.docs_assistant.starter_suggestions');
    }

    public function render()
    {
        return view('expert-statistics::livewire.docs.assistant-chat');
    }
}
