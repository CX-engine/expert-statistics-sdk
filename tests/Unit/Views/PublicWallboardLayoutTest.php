<?php

use CXEngine\ExpertStatistics\Tests\TestCase;

uses(TestCase::class);

/*
 * The public wallboard runs unattended on a TV. Livewire's default for a
 * failed poll is to render the error response (e.g. nginx's "502 Bad
 * Gateway" while PHP-FPM restarts during a deploy) in a modal that only a
 * click dismisses, so one failed tick froze the board on the error page.
 */

it('keeps failed polls out of the error modal so the board recovers on its own', function () {
    $html = view('expert-statistics::livewire.wallboard.layout', ['slot' => ''])->render();

    expect($html)
        ->toContain("document.addEventListener('livewire:init'")
        ->toContain('Livewire.interceptRequest(')
        ->toContain('preventDefault()')
        ->toContain('onSuccess(');
});

it('reloads only after the server answers again, on a lost session or repeated failures', function () {
    $html = view('expert-statistics::livewire.wallboard.layout', ['slot' => ''])->render();

    expect($html)
        ->toContain('status === 419 || consecutiveFailures >= 3')
        ->toContain("fetch(window.location.href, { method: 'HEAD', cache: 'no-store' })")
        ->toContain('response.ok && window.location.reload()');
});
