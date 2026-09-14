<?php

namespace CXEngine\ExpertStatistics\Concerns;

/**
 * Non-aborting counterpart to AuthorizesExpertStatisticsModification: exposes
 * the same "does this user hold a modification_permissions permission?" check
 * as a plain boolean, for components that are viewable by anyone with plain
 * expert-statistics.view access (via AuthorizesExpertStatisticsAccess) but
 * whose write actions - saving a form, deleting a record, changing the
 * active host - must still be restricted to expert-statistics.modify (or the
 * broader wildcard).
 *
 * Blade views use canModify() to hide/disable edit affordances; component
 * methods that persist a change call ensureCanModify() as their first line
 * so the restriction holds even if a request reaches the method directly.
 */
trait ChecksExpertStatisticsModifyPermission
{
    public function canModify(): bool
    {
        $permissions = (array) config('expert-statistics-api.modification_permissions', []);
        $user = auth()->user();

        return $user !== null && collect($permissions)->contains(
            fn (string $permission): bool => $user->can($permission)
        );
    }

    protected function ensureCanModify(): void
    {
        abort_unless($this->canModify(), 403);
    }
}
