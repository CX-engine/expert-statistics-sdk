<?php

namespace CXEngine\ExpertStatistics\Concerns;

/**
 * Gates a Livewire full-page component behind the permission strings listed
 * in config('expert-statistics-api.permissions'). A user passes if they hold
 * any one of them (checked via the framework's own Gate — works transparently
 * with Spatie laravel-permission without a hard package dependency).
 *
 * Livewire calls `boot{TraitName}()` automatically on every request.
 */
trait AuthorizesExpertStatisticsAccess
{
    public function bootAuthorizesExpertStatisticsAccess(): void
    {
        $permissions = (array) config('expert-statistics-api.permissions', []);
        $user = auth()->user();

        $allowed = $user !== null && collect($permissions)->contains(
            fn (string $permission): bool => $user->can($permission)
        );

        abort_unless($allowed, 403);
    }
}
