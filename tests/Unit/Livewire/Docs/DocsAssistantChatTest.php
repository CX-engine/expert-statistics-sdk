<?php

use CXEngine\ExpertStatistics\Livewire\Docs\DocsAssistantChat;
use CXEngine\ExpertStatistics\Tests\TestCase;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\StructuredResponseFake;

uses(TestCase::class);

function fakeAnswer(?string $sectionId = null): StructuredResponseFake
{
    return StructuredResponseFake::make()->withStructured([
        'answer' => 'Here is how you do it.',
        'suggested_section_id' => $sectionId,
    ]);
}

it('starts with no messages', function () {
    Livewire::test(DocsAssistantChat::class)
        ->assertSet('messages', []);
});

it('does nothing for a blank question', function () {
    Livewire::test(DocsAssistantChat::class)
        ->set('question', '   ')
        ->call('ask')
        ->assertSet('messages', []);
});

it('appends a user turn and an assistant turn, then clears the input', function () {
    Prism::fake([fakeAnswer('pbx-settings')]);

    Livewire::test(DocsAssistantChat::class)
        ->set('question', 'How do I create a resource group?')
        ->call('ask')
        ->assertSet('question', '')
        ->assertSet('messages.0.role', 'user')
        ->assertSet('messages.0.content', 'How do I create a resource group?')
        ->assertSet('messages.1.role', 'assistant')
        ->assertSet('messages.1.content', 'Here is how you do it.')
        ->assertSet('messages.1.suggestedSectionId', 'pbx-settings');
});

it('clears the whole conversation on demand, with nothing left behind', function () {
    Prism::fake([fakeAnswer(), fakeAnswer()]);

    Livewire::test(DocsAssistantChat::class)
        ->set('question', 'First question')
        ->call('ask')
        ->call('clearConversation')
        ->assertSet('messages', []);
});

it('a starter suggestion asks its question immediately', function () {
    Prism::fake([fakeAnswer()]);

    Livewire::test(DocsAssistantChat::class)
        ->call('useSuggestion', 'How do I schedule a report?')
        ->assertSet('messages.0.content', 'How do I schedule a report?');
});

describe('suggestedUrl / goToSuggestion', function () {
    it('returns null for a section with no associated app route', function () {
        // 'permissions' is a documentation-only section - see DocsCatalog.
        $url = Livewire::test(DocsAssistantChat::class)->instance()->suggestedUrl('permissions');

        expect($url)->toBeNull();
    });

    it('returns a real URL for a section whose route is registered', function () {
        Route::get('/expert-stats/dashboard', fn () => '')->name('expert-stats.dashboard');

        $url = Livewire::test(DocsAssistantChat::class)->instance()->suggestedUrl('dashboard');

        expect($url)->toBe(route('expert-stats.dashboard'));
    });

    it('returns null when the section\'s route isn\'t registered in this app', function () {
        // 'dashboard' maps to route name expert-stats.dashboard in
        // DocsCatalog, but nothing registers it in this Testbench app.
        $url = Livewire::test(DocsAssistantChat::class)->instance()->suggestedUrl('dashboard');

        expect($url)->toBeNull();
    });

    it('redirects when the suggested section has a real route', function () {
        Route::get('/expert-stats/dashboard', fn () => '')->name('expert-stats.dashboard');

        Livewire::test(DocsAssistantChat::class)
            ->call('goToSuggestion', 'dashboard')
            ->assertRedirect(route('expert-stats.dashboard'));
    });

    it('asks the parent panel to switch to Browse mode when there is no route to link to', function () {
        Livewire::test(DocsAssistantChat::class)
            ->call('goToSuggestion', 'permissions')
            ->assertDispatched('docs-assistant.show-section', sectionId: 'permissions');
    });

    it('does nothing when asked to go to a null suggestion', function () {
        Livewire::test(DocsAssistantChat::class)
            ->call('goToSuggestion', null)
            ->assertNotDispatched('docs-assistant.show-section')
            ->assertNoRedirect();
    });
});
