<?php

namespace CXEngine\ExpertStatistics\Livewire\Wallboard;

use CXEngine\ExpertStatistics\Services\ExpertStatisticsService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Saloon\Exceptions\Request\Statuses\ForbiddenException;
use Throwable;

/**
 * Public, unauthenticated wallboard display, ported from
 * bluerocktelclients' resources/views/wallboard/public.blade.php. Meant to
 * sit chromeless on a TV/monitor, identified only by its share key - no
 * "active PBX host" or authenticated user is ever in scope here, which is
 * why this deliberately does NOT `use AuthorizesExpertStatisticsAccess`
 * (that trait 403s anyone who isn't logged in and permitted, which is
 * every single visitor to this page).
 *
 * The source polled via vanilla-JS fetch()/setInterval; this uses
 * Livewire's own wire:poll instead, calling refresh() on each tick.
 *
 * Livewire always wraps a routed full-page component in the host app's
 * default layout (config('livewire.layout')), even when the component's
 * own view is already a complete <html> document - that layout is where
 * the host app's authenticated nav/sidebar lives, which has no business
 * wrapping a chromeless public display. #[Layout] points at a pass-through
 * view so the wallboard's own markup is the only <html> in the response.
 */
#[Layout('expert-statistics::livewire.wallboard.layout')]
class PublicWallboard extends Component
{
    public string $key = '';

    /** @var array<string, mixed> */
    public array $wallboard = [];

    /** @var array<int, array<string, mixed>> */
    public array $tiles = [];

    /** @var array<string, mixed>|null */
    public ?array $trend = null;

    /**
     * 'inactive' (share link disabled/expired - source's 403 branch),
     * 'not_found' (unknown key - source's 404 branch, and the catch-all
     * for any other failure, so nothing bubbles into a 500 on this
     * unauthenticated route), or null while healthy.
     */
    public ?string $error = null;

    /**
     * Clamped 3-300s, taken from the wallboard's own layout.refresh_seconds
     * and baked into the rendered wire:poll directive. If the wallboard's
     * configured interval changes later, the running poll won't hot-update
     * to match - an accepted edge case, same trade-off the source made by
     * only rescheduling its own setInterval when the value changed.
     */
    public int $pollSeconds = 10;

    public ?string $lastUpdatedAt = null;

    public function mount(string $key): void
    {
        $this->key = $key;

        $this->refresh();
    }

    /**
     * Re-fetches the wallboard's live data. Called on mount and on every
     * wire:poll tick - mirrors the source's periodic fetch().
     */
    public function refresh(): void
    {
        try {
            $payload = app(ExpertStatisticsService::class)->getPublicWallboard($this->key);

            $this->wallboard = $payload['wallboard'] ?? [];
            $this->tiles = $payload['tiles'] ?? [];
            $this->trend = $payload['trend'] ?? null;
            $this->error = null;
            $this->pollSeconds = $this->clampRefreshSeconds($this->wallboard['layout']['refresh_seconds'] ?? null);
            $this->lastUpdatedAt = now()->toTimeString();
        } catch (ForbiddenException) {
            $this->error = 'inactive';
        } catch (Throwable) {
            // Covers the source's 404 branch (Saloon's NotFoundException)
            // plus any other transport/server failure - all read the same
            // generic "not found" message rather than leaking details.
            $this->error = 'not_found';
        }
    }

    private function clampRefreshSeconds(mixed $seconds): int
    {
        $seconds = (int) ($seconds ?: 10);

        return max(3, min(300, $seconds));
    }

    public function render(): View
    {
        return view('expert-statistics::livewire.wallboard.public');
    }
}
