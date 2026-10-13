<?php

use CXEngine\ExpertStatistics\Contracts\ChecksExpertStatisticsActivation;
use CXEngine\ExpertStatistics\Livewire\Docs\DocsAssistantChat;
use CXEngine\ExpertStatistics\Livewire\Docs\DocsHelperPanel;
use CXEngine\ExpertStatistics\Livewire\Reports\ShareReportModal;
use CXEngine\ExpertStatistics\Tests\TestCase;
use Illuminate\Foundation\Auth\User as GenericUser;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

uses(TestCase::class);

beforeEach(function () {
    $this->useHostComponentDoubles();
    Blade::anonymousComponentPath(__DIR__.'/../../Doubles/views/filament', 'filament');

    $this->mockClient = MockClient::global(['*' => MockResponse::make([], 200)]);
    $this->activation = new class implements ChecksExpertStatisticsActivation
    {
        public bool $active = true;

        public function isActive(): bool
        {
            return $this->active;
        }
    };
    $this->app->instance(ChecksExpertStatisticsActivation::class, $this->activation);

    $this->granted = [];
    Gate::before(fn ($user, string $ability): bool => in_array($ability, $this->granted, true));

    $user = new GenericUser;
    $user->id = 1;
    $user->email = 'jane@example.com';
    $this->actingAs($user);
});

afterEach(fn () => MockClient::destroyGlobal());

it('refuses the embedded components without any Expert Statistics permission', function (string $component) {
    Livewire::test($component)->assertForbidden();
})->with([
    'share modal' => [ShareReportModal::class],
    'docs panel' => [DocsHelperPanel::class],
    'docs assistant' => [DocsAssistantChat::class],
]);

it('opens the embedded components to every page permission they are embedded under', function (string $component, string $permission, ?string $configKey) {
    if ($configKey !== null) {
        config()->set("expert-statistics-api.{$configKey}", [$permission]);
    }
    $this->granted = [$permission];

    Livewire::test($component)->assertOk();
})->with([
    'share modal' => [ShareReportModal::class],
    'docs panel' => [DocsHelperPanel::class],
    'docs assistant' => [DocsAssistantChat::class],
])->with([
    'module' => ['expert-statistics.view', null],
    'dashboard only' => ['dashboard.view', null],
    'settings page' => ['pbx-hosts.edit', 'configuration_permissions'],
    'scheduled reports page' => ['standard-statistics.view', 'scheduled_reports_permissions'],
]);

it('stops answering once the permission is revoked', function () {
    $this->granted = ['expert-statistics.view'];
    $modal = Livewire::test(ShareReportModal::class)
        ->call('openSchedule', urlType: 'queue', startDate: '2026-09-01', endDate: '2026-09-30', elements: '800');

    $this->granted = [];

    $modal->call('submit')->assertForbidden();
    $this->mockClient->assertNotSent(fn ($request, $response): bool => isset($response->getPendingRequest()->body()?->all()['report_type']));
});

it('shares the Dashboard with dashboard-only access but not the report pages', function () {
    $this->granted = ['dashboard.view'];

    Livewire::test(ShareReportModal::class)
        ->call('openShare', startDate: '2026-09-01', endDate: '2026-09-30')
        ->assertSet('isOpen', true)
        ->call('submit')
        ->assertSet('isOpen', false);

    Livewire::test(ShareReportModal::class)
        ->call('openShare', urlType: 'queue', startDate: '2026-09-01', endDate: '2026-09-30', elements: '800')
        ->assertForbidden();
});

it('requires the subscription for report pages but not for the free Dashboard', function () {
    $this->granted = ['expert-statistics.view'];
    $this->activation->active = false;

    Livewire::test(ShareReportModal::class)
        ->call('openShare', startDate: '2026-09-01', endDate: '2026-09-30')
        ->assertSet('isOpen', true);

    Livewire::test(ShareReportModal::class)
        ->call('openShare', urlType: 'queue', startDate: '2026-09-01', endDate: '2026-09-30', elements: '800')
        ->assertStatus(402);
});
