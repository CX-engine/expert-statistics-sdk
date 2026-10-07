<?php

use CXEngine\ExpertStatistics\Livewire\Dashboard;
use CXEngine\ExpertStatistics\Livewire\Docs\DocsHelperPanel;
use CXEngine\ExpertStatistics\Livewire\Home;
use CXEngine\ExpertStatistics\Tests\TestCase;
use Illuminate\Foundation\Auth\User as GenericUser;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

uses(TestCase::class);

beforeEach(function () {
    Route::get('/expert-stats/dashboard', Dashboard::class)->name('expert-stats.dashboard');

    Gate::before(fn () => true);
    $user = new GenericUser;
    $user->id = 1;
    $this->actingAs($user);

    $this->useHostComponentDoubles();
});

it('offers the dashboard by default', function () {
    $routes = array_column(Livewire::test(Home::class)->instance()->getFeatures(), 'route');

    expect($routes)->toContain('expert-stats.dashboard');
});

describe('for a host that keeps its own dashboard', function () {
    beforeEach(fn () => config()->set('expert-statistics-api.dashboard_enabled', false));

    it('answers 404 on the dashboard page', function () {
        Livewire::test(Dashboard::class)->assertNotFound();
    });

    it('drops the dashboard card from the home page', function () {
        $routes = array_column(Livewire::test(Home::class)->instance()->getFeatures(), 'route');

        expect($routes)->not->toContain('expert-stats.dashboard')
            ->and($routes)->toContain('expert-stats.my-queues.report');
    });

    it('drops the dashboard item from the cluster navigation', function () {
        Livewire::test(Home::class)
            ->assertOk()
            ->assertDontSeeHtml('/expert-stats/dashboard');
    });

    it('drops the dashboard section from the documentation panel', function () {
        $sectionIds = collect(Livewire::test(DocsHelperPanel::class)->instance()->catalog())
            ->flatMap(fn (array $group): array => array_column($group['sections'], 'id'));

        expect($sectionIds)->not->toContain('dashboard')
            ->and($sectionIds)->toContain('my-queues');
    });
});
