<?php

use CXEngine\ExpertStatistics\Contracts\ProvidesTrainingParticipants;
use CXEngine\ExpertStatistics\Livewire\Training\Training;
use CXEngine\ExpertStatistics\Tests\Doubles\FakeTrainingParticipants;
use CXEngine\ExpertStatistics\Tests\TestCase;
use Illuminate\Foundation\Auth\User as GenericUser;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

uses(TestCase::class);

beforeEach(function () {
    Route::get('/expert-stats/home', fn () => 'home')->name('expert-stats.home');
    Route::get('/expert-stats/training', Training::class)->name('expert-stats.training');

    Gate::define('expert-statistics.view', fn () => true);
    $user = new GenericUser;
    $user->id = 1;
    $user->email = 'jane@example.com';
    $this->actingAs($user);

    $this->app->instance(ProvidesTrainingParticipants::class, new FakeTrainingParticipants);
});

it('wraps cluster pages in the host page shell and secondary navigation by default', function () {
    $this->useHostComponentDoubles();

    Livewire::test(Training::class)
        ->assertOk()
        ->assertSeeHtml('<h1>'.__('expert-statistics::pbx.training.title'))
        ->assertSeeHtml('lg:w-56 lg:shrink-0');
});

it('renders cluster pages without the host page shell or its Blade components when disabled', function () {
    config()->set('expert-statistics-api.page_shell', false);

    // No host <x-pages.index> / <x-menus.*> doubles are registered here: a
    // host such as a Filament panel has none, so the page must still compile.
    Livewire::test(Training::class)
        ->assertOk()
        ->assertDontSeeHtml('lg:w-56 lg:shrink-0')
        ->assertDontSeeHtml('<h1>')
        ->assertSee(__('expert-statistics::pbx.training.how_it_works'));
});
