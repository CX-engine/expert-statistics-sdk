<?php

declare(strict_types=1);

namespace CXEngine\ExpertStatistics\Support;

/**
 * Result of a single turn from the action-capable assistant
 * (Contracts\PerformsExpertStatisticsActions). Unlike DocsAssistantAnswer,
 * this can carry a pendingAction — a proposed mutation (create/schedule a
 * report, create a resource group) that has NOT been executed yet and
 * requires an explicit Confirm click (Livewire\Docs\DocsAssistantChat::confirmAction())
 * before anything is created. The model itself never triggers a mutation.
 */
final readonly class ActionAssistantTurn
{
    /**
     * @param  array{type: 'report'|'resource_group', parameters: array<string, mixed>, summary: string, missingFields: array<int, string>, readyToConfirm: bool}|null  $pendingAction
     */
    public function __construct(
        public string $answer,
        public ?array $pendingAction = null,
        public ?string $suggestedSectionId = null,
    ) {}
}
