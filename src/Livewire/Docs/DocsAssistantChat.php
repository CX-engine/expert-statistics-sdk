<?php

declare(strict_types=1);

namespace CXEngine\ExpertStatistics\Livewire\Docs;

use CXEngine\ExpertStatistics\Concerns\ChecksExpertStatisticsModifyPermission;
use CXEngine\ExpertStatistics\Contracts\AnswersDocsQuestions;
use CXEngine\ExpertStatistics\Contracts\PerformsExpertStatisticsActions;
use CXEngine\ExpertStatistics\Exceptions\AiFeaturesNotActivatedException;
use CXEngine\ExpertStatistics\Services\ExpertStatisticsService;
use CXEngine\ExpertStatistics\Support\DocsCatalog;
use Illuminate\Support\Facades\Route;
use Livewire\Component;
use Throwable;

/**
 * "Ask AI" floating chat bubble (bottom-right), a deliberately separate
 * floating button from the "Documentation" panel (Livewire\Docs\DocsHelperPanel,
 * bottom-left) rather than a tab inside it. Answers "how do I use this
 * feature" / "where do I find that" questions, grounded only in the
 * Markdown docs, and — when config('expert-statistics-api.ai_actions.enabled')
 * is true — can also PROPOSE creating/scheduling a report or creating a
 * resource group, which this class then validates and creates only after
 * an explicit human Confirm click (confirmAction()). The model itself never
 * executes a mutation.
 *
 * Two contracts, picked per-turn by the ai_actions.enabled flag:
 * - Contracts\AnswersDocsQuestions (default, unchanged since before this
 *   feature existed): read-only, architecturally cannot reach call data or
 *   mutate anything.
 * - Contracts\PerformsExpertStatisticsActions (only when the flag is on):
 *   can propose the two mutation skills above, and — via the tool-calling
 *   "gather" stage inside its default implementation — ask the existing AI
 *   Chat/analyser for data-driven judgment calls ("my busiest queues").
 *
 * Coordinates with DocsHelperPanel one-way: when a suggested doc section has
 * no dedicated app route to link to, this dispatches 'docs-assistant.show-section'
 * so that panel opens itself on that section - see goToSuggestion().
 *
 * No storage regardless of mode: $messages and $pendingAction live only in
 * this component's own public state for the current page session. Nothing
 * is written to the database, the cache, or the session. Reloading the page
 * loses the conversation (and any unconfirmed proposal) - that's
 * intentional, not a bug.
 */
class DocsAssistantChat extends Component
{
    use ChecksExpertStatisticsModifyPermission;

    public bool $open = false;

    /**
     * @var array<int, array{role: 'user'|'assistant', content: string, suggestedSectionId?: string|null}>
     */
    public array $messages = [];

    public string $question = '';

    /**
     * True from the moment the user's message is appended to $messages
     * until the assistant's reply is generated. Lets the browser render the
     * user's own message (and a "thinking" indicator) in one quick request,
     * before the slower LLM call happens in a second request - see
     * sendMessage()/getResponse() and the Alpine chaining in the view.
     */
    public bool $thinking = false;

    /**
     * @var array{type: 'report'|'resource_group', parameters: array<string, mixed>, summary: string, missingFields: array<int, string>, readyToConfirm: bool}|null
     */
    public ?array $pendingAction = null;

    public function openPanel(): void
    {
        $this->open = true;
    }

    public function closePanel(): void
    {
        $this->open = false;
    }

    public function actionsEnabled(): bool
    {
        return (bool) config('expert-statistics-api.ai_actions.enabled', false);
    }

    /**
     * Split into two calls so the user's own message can appear immediately
     * instead of waiting for the (slower) LLM round trip: sendMessage() is
     * the fast half (just appends to $messages), getResponse() is the slow
     * half (the actual model call). The view chains them client-side via
     * Alpine ($wire.sendMessage().then(() => $wire.getResponse())) so the
     * browser renders the user's message + a "thinking" indicator from the
     * first request before the second one even starts. ask() keeps both
     * steps in a single call for callers (tests, useSuggestion) that don't
     * need the two-request UX.
     */
    public function ask(): void
    {
        $this->sendMessage();
        $this->getResponse();
    }

    public function sendMessage(): void
    {
        $question = trim($this->question);

        if ($question === '') {
            return;
        }

        $this->messages[] = ['role' => 'user', 'content' => $question];
        $this->question = '';
        $this->thinking = true;
    }

    public function getResponse(): void
    {
        if (! $this->thinking) {
            return;
        }

        $this->thinking = false;

        $lastMessage = end($this->messages);

        if ($lastMessage === false || $lastMessage['role'] !== 'user') {
            return;
        }

        $question = $lastMessage['content'];

        $history = array_map(
            fn (array $turn) => ['role' => $turn['role'], 'content' => $turn['content']],
            array_slice($this->messages, 0, -1),
        );

        if ($this->actionsEnabled()) {
            $turn = app(PerformsExpertStatisticsActions::class)
                ->handle($question, app()->getLocale(), $history, $this->pendingAction);

            $this->messages[] = [
                'role' => 'assistant',
                'content' => $turn->answer,
                'suggestedSectionId' => $turn->suggestedSectionId,
            ];
            $this->pendingAction = $turn->pendingAction;

            return;
        }

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
        $this->sendMessage();
    }

    public function clearConversation(): void
    {
        $this->messages = [];
        $this->pendingAction = null;
    }

    /**
     * Validates then creates the pending report/resource group — the ONLY
     * path in this whole feature that actually mutates anything, and only
     * ever reachable from an explicit user click on the confirm card, never
     * from the model or from parsing a chat message as consent.
     */
    public function confirmAction(): void
    {
        if ($this->pendingAction === null) {
            return;
        }

        if (! $this->canModify()) {
            $this->appendAssistantMessage(__('expert-statistics::pbx.docs_assistant.action_permission_denied'));
            $this->pendingAction = null;

            return;
        }

        $type = $this->pendingAction['type'];
        $parameters = $this->pendingAction['parameters'];
        $service = app(ExpertStatisticsService::class);

        try {
            $validation = $type === 'report'
                ? $service->validateAiHelperReport($parameters)
                : $service->validateAiHelperResourceGroup($parameters);
        } catch (Throwable $e) {
            report($e);
            $this->appendAssistantMessage(__('expert-statistics::pbx.docs_assistant.action_error'));

            return;
        }

        if (! ($validation['valid'] ?? false)) {
            $this->appendAssistantMessage(__('expert-statistics::pbx.docs_assistant.action_validation_failed', [
                'errors' => $this->flattenValidationErrors($validation['errors'] ?? []),
            ]));

            // Deliberately NOT clearing pendingAction: the proposal stays on
            // screen so the user (or the model, next turn) can correct it —
            // createAiHelper* is never called after a failed validate.
            return;
        }

        try {
            $type === 'report'
                ? $service->createAiHelperReport($parameters)
                : $service->createAiHelperResourceGroup($parameters);
        } catch (AiFeaturesNotActivatedException) {
            $this->appendAssistantMessage(__('expert-statistics::pbx.docs_assistant.action_not_activated'));
            $this->pendingAction = null;

            return;
        } catch (Throwable $e) {
            report($e);
            $this->appendAssistantMessage(__('expert-statistics::pbx.docs_assistant.action_error'));

            return;
        }

        $this->appendAssistantMessage($type === 'report'
            ? __('expert-statistics::pbx.docs_assistant.action_report_created')
            : __('expert-statistics::pbx.docs_assistant.action_group_created'));
        $this->pendingAction = null;
    }

    public function cancelAction(): void
    {
        if ($this->pendingAction === null) {
            return;
        }

        $this->pendingAction = null;
        $this->appendAssistantMessage(__('expert-statistics::pbx.docs_assistant.action_cancelled'));
    }

    /**
     * "What can I do?" button — a canned, non-LLM message (deterministic,
     * free, matches the starter-suggestions pattern), not a model call.
     */
    public function explainCapabilities(): void
    {
        $key = $this->actionsEnabled()
            ? 'expert-statistics::pbx.docs_assistant.capabilities_message_with_actions'
            : 'expert-statistics::pbx.docs_assistant.capabilities_message';

        $this->appendAssistantMessage(__($key));
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

    private function appendAssistantMessage(string $content): void
    {
        $this->messages[] = ['role' => 'assistant', 'content' => $content];
    }

    /**
     * @param  array<string, array<int, string>>  $errors
     */
    private function flattenValidationErrors(array $errors): string
    {
        return collect($errors)->flatten()->implode(' ');
    }

    public function render()
    {
        return view('expert-statistics::livewire.docs.assistant-chat');
    }
}
