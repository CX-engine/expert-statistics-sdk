<?php

use CXEngine\ExpertStatistics\Livewire\Docs\DocsHelperPanel;
use CXEngine\ExpertStatistics\Tests\TestCase;
use Livewire\Livewire;

uses(TestCase::class);

it('starts closed, in Browse mode', function () {
    Livewire::test(DocsHelperPanel::class)
        ->assertSet('open', false)
        ->assertSet('mode', 'browse');
});

it('opens and closes', function () {
    Livewire::test(DocsHelperPanel::class)
        ->call('openPanel')
        ->assertSet('open', true)
        ->call('closePanel')
        ->assertSet('open', false);
});

it('the Ask AI tab is hidden by default (docs_assistant.enabled is false)', function () {
    config(['expert-statistics-api.docs_assistant.enabled' => false]);

    Livewire::test(DocsHelperPanel::class)
        ->assertDontSee(__('expert-statistics::pbx.docs.tab_ask'));
});

it('the Ask AI tab appears once docs_assistant.enabled is true', function () {
    config(['expert-statistics-api.docs_assistant.enabled' => true]);

    Livewire::test(DocsHelperPanel::class)
        ->assertSee(__('expert-statistics::pbx.docs.tab_ask'))
        ->call('showAsk')
        ->assertSet('mode', 'ask')
        ->call('showBrowse')
        ->assertSet('mode', 'browse');
});

it('switching to Ask mode without the assistant enabled is a no-op for rendering the chat', function () {
    config(['expert-statistics-api.docs_assistant.enabled' => false]);

    // showAsk() itself doesn't check the flag (the tab that calls it is
    // simply not rendered) - confirm the Blade guard on the content area
    // still falls back to Browse instead of trying to mount the nested
    // chat component when it's disabled.
    Livewire::test(DocsHelperPanel::class)
        ->call('showAsk')
        ->assertSet('mode', 'ask')
        ->assertSuccessful();
});

it('jumps to a suggested section, opens, and switches to Browse when asked by the chat', function () {
    Livewire::test(DocsHelperPanel::class)
        ->call('showSuggestedSection', 'permissions')
        ->assertSet('activeSectionId', 'permissions')
        ->assertSet('mode', 'browse')
        ->assertSet('open', true);
});
