<?php

namespace CXEngine\ExpertStatistics\Concerns;

/**
 * Gates a Configuration Livewire component behind the permission strings
 * listed in config('expert-statistics-api.modification_permissions'). A user
 * passes if they hold any one of them.
 *
 * Deliberately stricter than AuthorizesExpertStatisticsAccess: holding only
 * expert-statistics.view (read-only report access) is NOT enough here - a
 * client needs the dedicated expert-statistics.modify permission (or the
 * broader expert-statistics.* wildcard, which already implies it) to reach
 * configuration pages like host selection or PBX settings.
 *
 * Livewire calls `boot{TraitName}()` automatically on every request.
 */
trait AuthorizesExpertStatisticsModification
{
    public function bootAuthorizesExpertStatisticsModification(): void
    {
        $permissions = (array) config('expert-statistics-api.modification_permissions', []);
        $user = auth()->user();

        $allowed = $user !== null && collect($permissions)->contains(
            fn (string $permission): bool => $user->can($permission)
        );

        abort_unless($allowed, 403);
    }
}
