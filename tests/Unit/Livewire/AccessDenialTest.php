<?php

use CXEngine\ExpertStatistics\Contracts\ChecksExpertStatisticsActivation;
use CXEngine\ExpertStatistics\Livewire\Reports\MyQueues\MyQueuesReport;
use CXEngine\ExpertStatistics\Livewire\Reports\ShareReportModal;
use CXEngine\ExpertStatistics\Tests\TestCase;
use Illuminate\Foundation\Auth\User as GenericUser;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

uses(TestCase::class);

beforeEach(function () {
    MockClient::global(['*' => MockResponse::make([], 200)]);

    $this->activation = new class implements ChecksExpertStatisticsActivation
    {
        public bool $active = true;

        public function isActive(): bool
        {
            return $this->active;
        }
    };
    $this->app->instance(ChecksExpertStatisticsActivation::class, $this->activation);

    $this->granted = ['expert-statistics.view'];
    Gate::before(fn ($user, string $ability): bool => in_array($ability, $this->granted, true));

    config()->set('expert-statistics-api.page_shell', false);
    $this->actingAs(tap(new GenericUser, fn (GenericUser $user) => $user->id = 1));

    Route::get('/report', MyQueuesReport::class)->middleware('web');
    Route::get('/unavailable/{reason}', fn (string $reason) => $reason)->name('unavailable');
    Route::getRoutes()->refreshNameLookups();
});

afterEach(fn () => MockClient::destroyGlobal());

it('redirects to the host page instead of aborting once the host app names one', function (array $granted, bool $active, string $reason) {
    config()->set('expert-statistics-api.unavailable_route', 'unavailable');
    $this->granted = $granted;
    $this->activation->active = $active;

    $this->get('/report')->assertRedirect(route('unavailable', ['reason' => $reason]));
})->with([
    'without permission' => [[], true, 'forbidden'],
    'without subscription' => [['expert-statistics.view'], false, 'not-activated'],
]);

it('aborts with the status when the host app names no page', function (array $granted, bool $active, int $status) {
    $this->granted = $granted;
    $this->activation->active = $active;

    $this->get('/report')->assertStatus($status);
})->with([
    'without permission' => [[], true, 403],
    'without subscription' => [['expert-statistics.view'], false, 402],
]);

it('aborts when the named route does not exist', function () {
    config()->set('expert-statistics-api.unavailable_route', 'missing');
    $this->granted = [];

    $this->get('/report')->assertForbidden();
});

it('redirects a refused Livewire update too', function () {
    config()->set('expert-statistics-api.unavailable_route', 'unavailable');
    $this->activation->active = false;

    Livewire::test(ShareReportModal::class)
        ->call('openShare', urlType: 'queue', startDate: '2026-09-01', endDate: '2026-09-30', elements: '800')
        ->assertStatus(302)
        ->assertHeader('Location', route('unavailable', ['reason' => 'not-activated']));
});
