<?php

namespace CXEngine\ExpertStatistics\Concerns;

/**
 * Gates the Dashboard Livewire component behind the permission strings listed
 * in config('expert-statistics-api.dashboard_permissions'). A user passes if
 * they hold any one of them.
 *
 * Split out from AuthorizesExpertStatisticsAccess (used by every other page
 * in this package) so a host app can grant "Dashboard only" access via
 * dashboard.* without also unlocking the 10 report/config/AI pages that share
 * the broader expert-statistics.* permission - while a user who already holds
 * expert-statistics.* keeps seeing the Dashboard too, since the default
 * dashboard_permissions list includes both.
 *
 * Livewire calls `boot{TraitName}()` automatically on every request.
 */
trait AuthorizesDashboardAccess
{
    public function bootAuthorizesDashboardAccess(): void
    {
        $permissions = (array) config('expert-statistics-api.dashboard_permissions', []);
        $user = auth()->user();

        $allowed = $user !== null && collect($permissions)->contains(
            fn (string $permission): bool => $user->can($permission)
        );

        abort_unless($allowed, 403);
    }
}
