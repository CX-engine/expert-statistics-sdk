<?php

use CXEngine\ExpertStatistics\Contracts\ChecksExpertStatisticsActivation;
use CXEngine\ExpertStatistics\Livewire\Configuration\ManagePbxSettings;
use CXEngine\ExpertStatistics\Livewire\Reports\ManageScheduledReports;
use CXEngine\ExpertStatistics\Livewire\Reports\MyQueues\MyQueuesReport;
use CXEngine\ExpertStatistics\Tests\TestCase;
use Illuminate\Foundation\Auth\User as GenericUser;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

uses(TestCase::class);

beforeEach(function () {
    MockClient::global(['*' => MockResponse::make([], 200)]);

    $this->app->instance(ChecksExpertStatisticsActivation::class, new class implements ChecksExpertStatisticsActivation
    {
        public function isActive(): bool
        {
            return true;
        }
    });

    config()->set('expert-statistics-api.page_shell', false);
    $this->actingAs(tap(new GenericUser, fn (GenericUser $user) => $user->id = 1));
});

afterEach(fn () => MockClient::destroyGlobal());

/**
 * @param  array<int, string>  $granted
 */
function grantOnly(array $granted): void
{
    Gate::before(fn ($user, string $ability): bool => in_array($ability, $granted, true));
}

it('opens the settings and scheduled reports pages with the module permissions by default', function () {
    grantOnly(['expert-statistics.view']);

    Livewire::test(ManagePbxSettings::class)->assertOk();
    Livewire::test(ManageScheduledReports::class)->assertOk();
});

it('opens the settings page with its own permissions when the host app sets them', function () {
    config()->set('expert-statistics-api.configuration_permissions', ['pbx-hosts.edit']);
    grantOnly(['pbx-hosts.edit']);

    Livewire::test(ManagePbxSettings::class)->assertOk();
    Livewire::test(MyQueuesReport::class)->assertForbidden();
});

it('refuses the settings page to module viewers once the host app narrows it', function () {
    config()->set('expert-statistics-api.configuration_permissions', ['pbx-hosts.edit']);
    grantOnly(['expert-statistics.view']);

    Livewire::test(ManagePbxSettings::class)->assertForbidden();
});

it('opens the scheduled reports page with its own permissions when the host app sets them', function () {
    config()->set('expert-statistics-api.scheduled_reports_permissions', ['standard-statistics.view']);
    grantOnly(['standard-statistics.view']);

    Livewire::test(ManageScheduledReports::class)->assertOk();
    Livewire::test(MyQueuesReport::class)->assertForbidden();
});
