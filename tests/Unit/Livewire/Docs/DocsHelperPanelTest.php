<?php

use CXEngine\ExpertStatistics\Livewire\Docs\DocsHelperPanel;
use CXEngine\ExpertStatistics\Tests\TestCase;
use Livewire\Livewire;

uses(TestCase::class);

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
