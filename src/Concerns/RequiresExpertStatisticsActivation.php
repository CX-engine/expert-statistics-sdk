<?php

namespace CXEngine\ExpertStatistics\Concerns;

use CXEngine\ExpertStatistics\Contracts\ChecksExpertStatisticsActivation;
use CXEngine\ExpertStatistics\Support\AccessDenial;

/**
 * Gates a Livewire full-page component behind
 * ChecksExpertStatisticsActivation::isActive(). Unlike
 * AuthorizesExpertStatisticsAccess (a user-permission gate applied to every
 * page, including the free Dashboard), this trait is only applied to the paid
 * report pages — it refuses with 402 (Payment Required) rather than 403, since
 * failing this check means "not subscribed", not "not permitted" (see
 * AccessDenial for the host's own page taking the place of both).
 *
 * Livewire calls `boot{TraitName}()` automatically on every request.
 */
trait RequiresExpertStatisticsActivation
{
    public function bootRequiresExpertStatisticsActivation(): void
    {
        if (! app(ChecksExpertStatisticsActivation::class)->isActive()) {
            AccessDenial::notActivated();
        }
    }
}
