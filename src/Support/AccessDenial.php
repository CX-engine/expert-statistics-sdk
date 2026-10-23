<?php

namespace CXEngine\ExpertStatistics\Support;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;

/**
 * Refuses a request to an Expert Statistics component. A host app that names
 * its own page for it (config `unavailable_route`) gets the user sent there,
 * inside its own layout, with the reason as the `reason` route parameter;
 * otherwise the request aborts with the status, rendered by the host's error
 * views.
 *
 * The redirect is thrown as an HttpResponseException around a plain
 * RedirectResponse rather than built with redirect(): Livewire rebinds
 * `redirect` to its own Redirector during a component's lifecycle, which is
 * not an HTTP response. Laravel's view engine lets this exception through
 * unwrapped, so it also ends a page that embeds the refused component. A
 * Livewire update or wire:navigate fetch follows the redirect, and Livewire
 * then loads the page it lands on.
 */
final class AccessDenial
{
    public const FORBIDDEN = 'forbidden';

    public const NOT_ACTIVATED = 'not-activated';

    public static function forbidden(): never
    {
        self::deny(403, self::FORBIDDEN);
    }

    public static function notActivated(): never
    {
        self::deny(402, self::NOT_ACTIVATED, __('expert-statistics::pbx.activation.required'));
    }

    private static function deny(int $status, string $reason, string $message = ''): never
    {
        $route = config('expert-statistics-api.unavailable_route');

        if (is_string($route) && Route::has($route)) {
            throw new HttpResponseException(new RedirectResponse(route($route, ['reason' => $reason])));
        }

        abort($status, $message);
    }
}
