<?php

declare(strict_types=1);

namespace CXEngine\ExpertStatistics\Services;

use CXEngine\ExpertStatistics\Contracts\AnswersDocsQuestions;
use CXEngine\ExpertStatistics\Support\DocsAssistantAnswer;
use CXEngine\ExpertStatistics\Support\DocsCatalog;
use CXEngine\ExpertStatistics\Support\DocsContent;
use CXEngine\ExpertStatistics\Support\DocsSearch;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;
use Prism\Prism\ValueObjects\Messages\AssistantMessage;
use Prism\Prism\ValueObjects\Messages\UserMessage;
use Throwable;

/**
 * Default AnswersDocsQuestions implementation: retrieves the doc sections
 * most relevant to the question (Support\DocsSearch, plain lexical scoring -
 * no embeddings/vector DB), feeds only that excerpt text to an LLM via
 * Prism, and asks for a grounded answer plus (optionally) the single most
 * relevant section id to deep-link to.
 *
 * Architecturally incapable of answering data questions: this class never
 * receives an ExpertStatisticsService instance, never queries the XP-Stats
 * API, and never persists a conversation anywhere (the Livewire component
 * holds messages only in its own in-memory public state for the page
 * session - see Livewire\Docs\DocsAssistantChat).
 */
class PrismDocsAssistantResponder implements AnswersDocsQuestions
{
    /**
     * Where any real call-data/statistics/analysis question must be
     * redirected to, per the system prompt's rule 1 - the AI Chat page
     * (Livewire\Ai\AiChat), a *different* feature from this assistant that
     * actually is grounded in the user's real call data. Its excerpt is
     * always included in context (see answer()) so the model has real
     * grounding for *why* to redirect there, not just an id to parrot back.
     */
    private const DATA_QUESTION_REDIRECT_SECTION_ID = 'ai-insights';

    public function answer(string $question, string $locale, array $history = []): DocsAssistantAnswer
    {
        $resolvedLocale = DocsCatalog::resolveLocale($locale);
        $maxSections = (int) config('expert-statistics-api.docs_assistant.max_context_sections', 3);

        $sectionIds = DocsSearch::topSectionIds($question, $resolvedLocale, $maxSections);

        if (! in_array(self::DATA_QUESTION_REDIRECT_SECTION_ID, $sectionIds, true)) {
            $sectionIds[] = self::DATA_QUESTION_REDIRECT_SECTION_ID;
        }

        $excerpts = $this->excerpts($sectionIds, $resolvedLocale);

        try {
            $response = Prism::structured()
                ->using(
                    Provider::from((string) config('expert-statistics-api.docs_assistant.provider', 'mistral')),
                    (string) config('expert-statistics-api.docs_assistant.model', 'mistral-small-latest'),
                )
                ->withSchema($this->schema())
                ->withSystemPrompt(view('expert-statistics::prompts.docs-assistant-system', [
                    'locale' => $resolvedLocale,
                    'knownSectionIds' => array_column(DocsCatalog::sections(), 'id'),
                    'dataRedirectSectionId' => self::DATA_QUESTION_REDIRECT_SECTION_ID,
                    'excerpts' => $excerpts,
                ]))
                ->withMessages([...$this->historyMessages($history), new UserMessage($question)])
                ->withClientRetry(times: 1, sleepMilliseconds: 500)
                ->withClientOptions(['timeout' => 20])
                ->asStructured();
        } catch (Throwable $e) {
            report($e);

            return new DocsAssistantAnswer(
                answer: __('expert-statistics::pbx.docs_assistant.error_message'),
                sourceSectionIds: $sectionIds,
            );
        }

        $data = $response->structured;

        if ($data === null || ! isset($data['answer'])) {
            return new DocsAssistantAnswer(
                answer: __('expert-statistics::pbx.docs_assistant.error_message'),
                sourceSectionIds: $sectionIds,
            );
        }

        $suggestedSectionId = $data['suggested_section_id'] ?? null;

        // Defensive: discard anything the model returns that isn't a real
        // catalog id, rather than trusting it to only ever pick from the
        // list it was given.
        if ($suggestedSectionId !== null && DocsCatalog::find($suggestedSectionId) === null) {
            $suggestedSectionId = null;
        }

        return new DocsAssistantAnswer(
            answer: (string) $data['answer'],
            suggestedSectionId: $suggestedSectionId,
            sourceSectionIds: $sectionIds,
        );
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

    private function schema(): ObjectSchema
    {
        return new ObjectSchema(
            name: 'docs_assistant_answer',
            description: 'A grounded answer about how to use the Expert Statistics module',
            properties: [
                new StringSchema(
                    name: 'answer',
                    description: 'The answer to the user\'s question, in the same language they asked in. Plain text (no Markdown formatting).',
                ),
                new StringSchema(
                    name: 'suggested_section_id',
                    description: 'The single most relevant documentation section id for this question, chosen only from the provided list of known section ids. If this question asks for real call data/statistics/analysis, this MUST be set to "'.self::DATA_QUESTION_REDIRECT_SECTION_ID.'" (the AI Chat page) - see system prompt rule 1. Omit / null if nothing is genuinely relevant.',
                    nullable: true,
                ),
            ],
            requiredFields: ['answer'],
        );
    }
}
