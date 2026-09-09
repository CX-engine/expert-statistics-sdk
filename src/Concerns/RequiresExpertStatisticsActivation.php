<?php

namespace CXEngine\ExpertStatistics\Concerns;

use CXEngine\ExpertStatistics\Contracts\ChecksExpertStatisticsActivation;

/**
 * Gates a Livewire full-page component behind
 * ChecksExpertStatisticsActivation::isActive(). Unlike
 * AuthorizesExpertStatisticsAccess (a user-permission gate applied to every
 * page, including the free Dashboard), this trait is only applied to the paid
 * report pages — it aborts with 402 (Payment Required) rather than 403, since
 * failing this check means "not subscribed", not "not permitted".
 *
 * Livewire calls `boot{TraitName}()` automatically on every request.
 */
trait RequiresExpertStatisticsActivation
{
    public function bootRequiresExpertStatisticsActivation(): void
    {
        abort_unless(
            app(ChecksExpertStatisticsActivation::class)->isActive(),
            402,
            __('expert-statistics::pbx.activation.required')
        );
    }
}
