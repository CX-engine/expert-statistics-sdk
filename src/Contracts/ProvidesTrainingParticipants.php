<?php

declare(strict_types=1);

namespace CXEngine\ExpertStatistics\Contracts;

/**
 * Host-app side of the Expert Statistics training (Livewire\Training\Training):
 * whether the training is offered in the current request, which client
 * tenants a participant can pick, and whether an email was invited to one
 * of them. The package (and the expert-stats backend) have no knowledge of
 * the host app's tenants or users, so the host app binds this contract.
 *
 * Defaults to Support\NoTrainingParticipants (training never available)
 * when the host app doesn't bind it.
 */
interface ProvidesTrainingParticipants
{
    /**
     * Whether the Training page/nav item is offered in the current request
     * (e.g. only for a demo tenant and host).
     */
    public function isTrainingAvailable(): bool;

    /**
     * Client tenants a participant can request a training for.
     *
     * @return array<int, array{id: string, name: string, code: string|null}>
     */
    public function getTrainingTenants(): array;

    /**
     * Whether `$email` belongs to the users invited to tenant `$tenantId`.
     */
    public function isInvitedToTenant(string $tenantId, string $email): bool;
}
