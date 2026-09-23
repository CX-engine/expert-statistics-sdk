<?php

declare(strict_types=1);

namespace CXEngine\ExpertStatistics\Support;

/**
 * Result of a single docs assistant turn. Never carries call-data/KPI values
 * - AnswersDocsQuestions implementations are only ever given documentation
 * excerpts, so there is nothing else for this to contain.
 */
final readonly class DocsAssistantAnswer
{
    /**
     * @param  array<int, string>  $sourceSectionIds  doc sections used as grounding context for this answer
     */
    public function __construct(
        public string $answer,
        public ?string $suggestedSectionId = null,
        public array $sourceSectionIds = [],
    ) {}
}
