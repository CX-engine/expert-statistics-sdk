<?php

use CXEngine\ExpertStatistics\Livewire\Docs\DocsHelperPanel;
use CXEngine\ExpertStatistics\Tests\TestCase;
use Illuminate\Foundation\Auth\User as GenericUser;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

uses(TestCase::class);

beforeEach(function () {
    Gate::define('expert-statistics.view', fn () => true);
    $this->actingAs(tap(new GenericUser, fn (GenericUser $user) => $user->id = 1));
});

it('starts closed', function () {
    Livewire::test(DocsHelperPanel::class)
        ->assertSet('open', false);
});

it('opens and closes', function () {
    Livewire::test(DocsHelperPanel::class)
        ->call('openPanel')
        ->assertSet('open', true)
        ->call('closePanel')
        ->assertSet('open', false);
});

it('jumps to a suggested section and opens, when asked by the docs assistant chat', function () {
    Livewire::test(DocsHelperPanel::class)
        ->call('showSuggestedSection', 'permissions')
        ->assertSet('activeSectionId', 'permissions')
        ->assertSet('open', true);
});

it('places the floating trigger in the page area, clear of the host app sidebar', function () {
    Livewire::test(DocsHelperPanel::class)
        ->assertSeeHtml('fixed bottom-6 left-6 lg:left-74');
});
