<?php

declare(strict_types=1);

namespace CXEngine\ExpertStatistics\Tests\Doubles;

use CXEngine\ExpertStatistics\Contracts\ProvidesTrainingParticipants;

/**
 * ProvidesTrainingParticipants double: training available, two tenants,
 * `jane@example.com` invited to `tenant-1` only.
 */
class FakeTrainingParticipants implements ProvidesTrainingParticipants
{
    /**
     * @param  array<string, array<int, string>>  $invites  tenant id => invited emails
     */
    public function __construct(
        public bool $available = true,
        public array $invites = ['tenant-1' => ['jane@example.com']],
    ) {}

    public function isTrainingAvailable(): bool
    {
        return $this->available;
    }

    public function getTrainingTenants(): array
    {
        return [
            ['id' => 'tenant-1', 'name' => 'Acme', 'code' => '0001'],
            ['id' => 'tenant-2', 'name' => 'Globex', 'code' => null],
        ];
    }

    public function isInvitedToTenant(string $tenantId, string $email): bool
    {
        return in_array($email, $this->invites[$tenantId] ?? [], true);
    }
}
