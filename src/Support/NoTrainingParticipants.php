<?php

declare(strict_types=1);

namespace CXEngine\ExpertStatistics\Support;

use CXEngine\ExpertStatistics\Contracts\ProvidesTrainingParticipants;

/**
 * Default ProvidesTrainingParticipants binding: the training is not offered
 * unless the host app binds its own implementation.
 */
class NoTrainingParticipants implements ProvidesTrainingParticipants
{
    public function isTrainingAvailable(): bool
    {
        return false;
    }

    public function getTrainingTenants(): array
    {
        return [];
    }

    public function isInvitedToTenant(string $tenantId, string $email): bool
    {
        return false;
    }
}
