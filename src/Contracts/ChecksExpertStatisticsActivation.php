<?php

namespace CXEngine\ExpertStatistics\Contracts;

/**
 * Determines whether the (paid) Expert Statistics feature — as opposed to the
 * free Dashboard — is currently usable for the active PBX host in this
 * request. The package never assumes how "active" is decided (subscription,
 * trial, etc.) — the host app binds this contract.
 */
interface ChecksExpertStatisticsActivation
{
    public function isActive(): bool;
}
