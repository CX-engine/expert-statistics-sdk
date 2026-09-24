<?php

declare(strict_types=1);

namespace CXEngine\ExpertStatistics\Services;

use CXEngine\ExpertStatistics\Contracts\PerformsExpertStatisticsActions;
use CXEngine\ExpertStatistics\Support\ActionAssistantTurn;
use CXEngine\ExpertStatistics\Support\DocsCatalog;
use CXEngine\ExpertStatistics\Support\DocsContent;
use CXEngine\ExpertStatistics\Support\DocsSearch;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Facades\Tool;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\BooleanSchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;
use Prism\Prism\ValueObjects\Messages\AssistantMessage;
use Prism\Prism\ValueObjects\Messages\UserMessage;
use Throwable;

/**
 * Default PerformsExpertStatisticsActions implementation. Two Prism calls
 * per turn:
 *
 * 1. "Gather" (Prism::text()->withTools()->withMaxSteps()) — the model may
 *    freely call the read-only ask_call_analytics tool (wrapping
 *    ExpertStatisticsService::askCallAnalytics(), i.e. the existing AI Chat
 *    integration) when the request needs a data-driven judgment call
 *    ("my busiest queues"). Never mutates anything - safe to let the model
 *    call it without confirmation.
 * 2. "Finalize" (Prism::structured()) — given the gather stage's findings,
 *    doc excerpts (same retrieval as PrismDocsAssistantResponder, for plain
 *    how-to answers), the current in-flight pendingAction (so the model can
 *    revise a proposal turn over turn), and the available queues/extensions
 *    for the active host (grounding for name→id resolution) — produces the
 *    final answer, and optionally a pending_report or pending_resource_group
 *    proposal. The model NEVER executes a mutation itself; it only ever
 *    proposes one, which Livewire\Docs\DocsAssistantChat::confirmAction()
 *    validates then creates, only after an explicit human Confirm click.
 *
 * Unlike PrismDocsAssistantResponder, this class DOES depend on
 * ExpertStatisticsService (for analytics lookups and grounding element
 * lists) - see tests/Architecture/DocsAssistantArchTest.php for why that
 * boundary matters for the read-only assistant but not this one.
 */
class PrismActionAssistantResponder implements PerformsExpertStatisticsActions
{
    /** Same redirect target the read-only docs assistant uses for data questions. */
    private const DATA_QUESTION_REDIRECT_SECTION_ID = 'ai-insights';

    private const REPORT_TYPES = ['report', 'didReport', 'userReport', 'callerNumbersReport', 'dashboard'];

    private const REPEAT_PATTERNS = ['day', 'week', 'month'];

    /** String enum the model works with — mapped to the backend's 0/1/4/99 ints in normalizedPendingAction(). */
    private const RESOURCE_GROUP_TYPES = ['extension', 'did', 'queue', 'caller'];

    public function __construct(private readonly ExpertStatisticsService $service) {}

    public function handle(string $message, string $locale, array $history, ?array $pendingAction): ActionAssistantTurn
    {
        // Top-level safety net: gather()/finalize() each catch their own
        // Prism-call failures, but resolving $provider itself (an invalid
        // config value throws \ValueError from Provider::from(), a
        // Throwable, before either stage's own try/catch would ever run)
        // must not crash the whole turn either.
        try {
            $resolvedLocale = DocsCatalog::resolveLocale($locale);
            $provider = Provider::from((string) config('expert-statistics-api.ai_actions.provider', 'mistral'));
            $model = (string) config('expert-statistics-api.ai_actions.model', 'mistral-small-latest');
            $maxSteps = (int) config('expert-statistics-api.ai_actions.max_tool_steps', 4);

            $historyMessages = $this->historyMessages($history);

            $reasoning = $this->gather($provider, $model, $maxSteps, $resolvedLocale, $historyMessages, $message);

            return $this->finalize($provider, $model, $resolvedLocale, $historyMessages, $message, $pendingAction, $reasoning);
        } catch (Throwable $e) {
            report($e);

            return new ActionAssistantTurn(answer: __('expert-statistics::pbx.docs_assistant.error_message'));
        }
    }

    /**
     * @param  array<int, UserMessage|AssistantMessage>  $historyMessages
     */
    private function gather(Provider $provider, string $model, int $maxSteps, string $locale, array $historyMessages, string $message): string
    {
        $tool = Tool::as('ask_call_analytics')
            ->for('Ask a natural-language question about this PBX host\'s real call/queue/agent data (e.g. "what are the busiest queues this month?", "who are the most active agents?"). Returns a grounded, data-driven answer. Read-only — never use this to create or change anything.')
            ->withStringParameter('question', 'The question to ask, in plain language')
            ->using(fn (string $question): string => $this->askAnalyticsSafely($question));

        try {
            $response = Prism::text()
                ->using($provider, $model)
                ->withSystemPrompt(view('expert-statistics::prompts.action-assistant-gather-system', ['locale' => $locale]))
                ->withMessages([...$historyMessages, new UserMessage($message)])
                ->withTools([$tool])
                ->withMaxSteps($maxSteps)
                ->withClientRetry(times: 1, sleepMilliseconds: 500)
                ->withClientOptions(['timeout' => 30])
                ->asText();

            return $response->text;
        } catch (Throwable $e) {
            report($e);

            return '';
        }
    }

    private function askAnalyticsSafely(string $question): string
    {
        try {
            return json_encode($this->service->askCallAnalytics($question)) ?: 'No data available.';
        } catch (Throwable $e) {
            report($e);

            return 'Could not retrieve analytics data right now.';
        }
    }

    /**
     * @param  array<int, UserMessage|AssistantMessage>  $historyMessages
     * @param  array{type: 'report'|'resource_group', parameters: array<string, mixed>, summary: string, missingFields: array<int, string>, readyToConfirm: bool}|null  $pendingAction
     */
    private function finalize(Provider $provider, string $model, string $locale, array $historyMessages, string $message, ?array $pendingAction, string $reasoning): ActionAssistantTurn
    {
        $sectionIds = DocsSearch::topSectionIds($message, $locale, 3);

        if (! in_array(self::DATA_QUESTION_REDIRECT_SECTION_ID, $sectionIds, true)) {
            $sectionIds[] = self::DATA_QUESTION_REDIRECT_SECTION_ID;
        }

        try {
            $response = Prism::structured()
                ->using($provider, $model)
                ->withSchema($this->schema())
                ->withSystemPrompt(view('expert-statistics::prompts.action-assistant-finalize-system', [
                    'locale' => $locale,
                    'knownSectionIds' => array_column(DocsCatalog::sections(), 'id'),
                    'dataRedirectSectionId' => self::DATA_QUESTION_REDIRECT_SECTION_ID,
                    'excerpts' => $this->excerpts($sectionIds, $locale),
                    'reasoning' => $reasoning,
                    'pendingAction' => $pendingAction,
                    'availableQueues' => $this->availableQueues(),
                    'availableExtensions' => $this->availableExtensions(),
                    'availableResourceGroups' => $this->availableResourceGroups(),
                    'today' => now()->toDateString(),
                    'reportTypes' => self::REPORT_TYPES,
                    'repeatPatterns' => self::REPEAT_PATTERNS,
                    'resourceGroupTypes' => self::RESOURCE_GROUP_TYPES,
                ]))
                ->withMessages([...$historyMessages, new UserMessage($message)])
                ->withClientRetry(times: 1, sleepMilliseconds: 500)
                ->withClientOptions(['timeout' => 20])
                ->asStructured();
        } catch (Throwable $e) {
            report($e);

            return new ActionAssistantTurn(answer: __('expert-statistics::pbx.docs_assistant.error_message'));
        }

        $data = $response->structured;

        if ($data === null || ! isset($data['answer'])) {
            return new ActionAssistantTurn(answer: __('expert-statistics::pbx.docs_assistant.error_message'));
        }

        $suggestedSectionId = $data['suggested_section_id'] ?? null;

        if ($suggestedSectionId !== null && DocsCatalog::find($suggestedSectionId) === null) {
            $suggestedSectionId = null;
        }

        return new ActionAssistantTurn(
            answer: (string) $data['answer'],
            pendingAction: $this->normalizedPendingAction($data),
            suggestedSectionId: $suggestedSectionId,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{type: 'report'|'resource_group', parameters: array<string, mixed>, summary: string, missingFields: array<int, string>, readyToConfirm: bool}|null
     */
    private function normalizedPendingAction(array $data): ?array
    {
        if (! empty($data['pending_report']) && is_array($data['pending_report'])) {
            $p = $data['pending_report'];
            $groupId = $p['pbx3cx_host_resource_group_id'] ?? null;

            return [
                'type' => 'report',
                'parameters' => [
                    'name' => $p['name'] ?? null,
                    'report_type' => $p['report_type'] ?? null,
                    'element_type' => $p['element_type'] ?? null,
                    'dns' => $p['dns'] ?? null,
                    'pbx3cx_host_resource_group_id' => is_numeric($groupId) ? (int) $groupId : null,
                    'start' => $p['start'] ?? null,
                    'end' => $p['end'] ?? null,
                    'email' => $p['email'] ?? null,
                    'instant' => (bool) ($p['instant'] ?? false),
                    'repeat' => (bool) ($p['repeat'] ?? false),
                    'repeat_pattern' => $p['repeat_pattern'] ?? null,
                ],
                'summary' => (string) ($p['summary'] ?? ''),
                'missingFields' => is_array($p['missing_fields'] ?? null) ? $p['missing_fields'] : [],
                'readyToConfirm' => (bool) ($p['ready_to_confirm'] ?? false),
            ];
        }

        if (! empty($data['pending_resource_group']) && is_array($data['pending_resource_group'])) {
            $p = $data['pending_resource_group'];

            return [
                'type' => 'resource_group',
                'parameters' => [
                    'name' => $p['name'] ?? null,
                    'type' => $this->resourceGroupTypeToInt($p['type'] ?? null),
                    'resources' => is_array($p['members'] ?? null) ? array_values($p['members']) : [],
                ],
                'summary' => (string) ($p['summary'] ?? ''),
                'missingFields' => is_array($p['missing_fields'] ?? null) ? $p['missing_fields'] : [],
                'readyToConfirm' => (bool) ($p['ready_to_confirm'] ?? false),
            ];
        }

        return null;
    }

    private function resourceGroupTypeToInt(?string $type): ?int
    {
        return match ($type) {
            'extension' => 0,
            'did' => 1,
            'queue' => 4,
            'caller' => 99,
            default => null,
        };
    }

    /**
     * @param  array<int, string>  $sectionIds
     * @return array<int, array{id: string, content: string}>
     */
    private function excerpts(array $sectionIds, string $locale): array
    {
        return collect($sectionIds)
            ->map(fn (string $id): array => ['id' => $id, 'content' => DocsContent::raw($id, $locale)])
            ->filter(fn (array $excerpt): bool => trim($excerpt['content']) !== '')
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array{role: 'user'|'assistant', content: string}>  $history
     * @return array<int, UserMessage|AssistantMessage>
     */
    private function historyMessages(array $history): array
    {
        return array_map(
            fn (array $turn) => $turn['role'] === 'user'
                ? new UserMessage($turn['content'])
                : new AssistantMessage($turn['content']),
            $history,
        );
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function availableQueues(): array
    {
        try {
            $queues = $this->service->getMap()['call_queues'] ?? [];

            return array_values(array_map(
                fn (string $key, array $q): array => ['value' => $key, 'label' => $q['name'] ?? $key],
                array_keys($queues),
                array_values($queues),
            ));
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function availableExtensions(): array
    {
        try {
            $extensions = $this->service->getMap()['extensions'] ?? [];

            return array_values(array_map(
                fn (string $key, $label): array => ['value' => $key, 'label' => is_string($label) ? $label : $key],
                array_keys($extensions),
                array_values($extensions),
            ));
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Existing resource groups for the active host — the grounding a
     * "send a report to the Top 5 Agents group" style request needs to
     * resolve a NAME to a real pbx3cx_host_resource_group_id, instead of
     * the model inventing/guessing one. Without this, a referenced group
     * can never actually be matched, only talked about.
     *
     * @return array<int, array{id: int, name: string, type: string, members: array<int, string>}>
     */
    private function availableResourceGroups(): array
    {
        try {
            return collect($this->service->getResourceGroups())
                ->map(function (array $group): array {
                    $resources = $group['resources'] ?? [];

                    if (is_string($resources)) {
                        $resources = json_decode($resources, true) ?? [];
                    }

                    return [
                        'id' => (int) $group['id'],
                        'name' => (string) $group['name'],
                        'type' => $this->resourceGroupTypeToLabel((int) $group['type']),
                        'members' => array_map('strval', is_array($resources) ? $resources : []),
                    ];
                })
                ->values()
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    private function resourceGroupTypeToLabel(int $type): string
    {
        return match ($type) {
            0 => 'extension',
            1 => 'did',
            4 => 'queue',
            99 => 'caller',
            default => (string) $type,
        };
    }

    private function schema(): ObjectSchema
    {
        $missingFields = new ArraySchema(
            name: 'missing_fields',
            description: 'Field names still needed from the user before this can be confirmed',
            items: new StringSchema('field', 'A missing field name'),
        );

        $pendingReport = new ObjectSchema(
            name: 'pending_report',
            description: 'A proposed report to create/schedule — only set this when the user has asked to create or schedule a report and you have gathered at least some parameters for it',
            properties: [
                new StringSchema('name', 'A short human name for the report', nullable: true),
                new StringSchema('report_type', 'One of: '.implode(', ', self::REPORT_TYPES), nullable: true),
                new StringSchema('element_type', 'Element type code: 0 for extensions, 4 for queues, or * for all', nullable: true),
                new StringSchema('dns', 'Comma-separated queue/extension ids this report covers, resolved from the available queues/extensions list — leave null if using pbx3cx_host_resource_group_id instead', nullable: true),
                new StringSchema('pbx3cx_host_resource_group_id', 'The id of an existing resource group to scope this report to, if the user referenced one by name', nullable: true),
                new StringSchema('start', 'Start date/time, e.g. "2026-01-01 00:00:00"', nullable: true),
                new StringSchema('end', 'End date/time, e.g. "2026-01-07 23:59:59"', nullable: true),
                new StringSchema('email', 'Comma-separated recipient email addresses', nullable: true),
                new BooleanSchema('instant', 'True if this should be sent right now', nullable: true),
                new BooleanSchema('repeat', 'True if this should repeat on a schedule', nullable: true),
                new StringSchema('repeat_pattern', 'One of: '.implode(', ', self::REPEAT_PATTERNS).' — required when repeat is true', nullable: true),
                $missingFields,
                new StringSchema('summary', 'A short, plain-language summary of exactly what will be created, for the user to review before confirming', nullable: true),
                new BooleanSchema('ready_to_confirm', 'True only when every required field is present and you are presenting this as ready for the user to confirm', nullable: true),
            ],
            nullable: true,
        );

        $pendingResourceGroup = new ObjectSchema(
            name: 'pending_resource_group',
            description: 'A proposed resource group to create — only set this when the user has asked to create a group and you have gathered at least some parameters for it',
            properties: [
                new StringSchema('name', 'A short human name for the group', nullable: true),
                new StringSchema('type', 'One of: '.implode(', ', self::RESOURCE_GROUP_TYPES), nullable: true),
                new ArraySchema('members', 'The ids/numbers of the queues/extensions/DIDs/caller numbers in this group, resolved from the available list where applicable', new StringSchema('member', 'An id or number'), nullable: true),
                $missingFields,
                new StringSchema('summary', 'A short, plain-language summary of exactly what will be created, for the user to review before confirming', nullable: true),
                new BooleanSchema('ready_to_confirm', 'True only when every required field is present and you are presenting this as ready for the user to confirm', nullable: true),
            ],
            nullable: true,
        );

        return new ObjectSchema(
            name: 'action_assistant_turn',
            description: 'A turn from the Expert Statistics action-capable assistant',
            properties: [
                new StringSchema('answer', 'The reply to show the user, in the same language they asked in. Plain text, no Markdown formatting.'),
                new StringSchema('suggested_section_id', 'The most relevant documentation section id, chosen only from the known list, for a plain how-to or data-redirect answer. Null when proposing an action.', nullable: true),
                $pendingReport,
                $pendingResourceGroup,
            ],
            requiredFields: ['answer'],
        );
    }
}
