<?php

use CXEngine\ExpertStatistics\Contracts\ChecksExpertStatisticsActivation;
use CXEngine\ExpertStatistics\Livewire\CallAnalysis\CallAnalysis;
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

    MockClient::global(['*' => MockResponse::make([], 200)]);

    $this->app->instance(ChecksExpertStatisticsActivation::class, new class implements ChecksExpertStatisticsActivation
    {
        public function isActive(): bool
        {
            return true;
        }
    });

    Gate::before(fn () => true);
    $user = new GenericUser;
    $user->id = 1;
    $this->actingAs($user);
    app()->setLocale('en');
});

afterEach(function () {
    MockClient::destroyGlobal();
});

/** A call answered by VIALLIS Veronika, then transferred to the PIECES_RECHANGES queue where nobody answered. */
function cardsCall(): array
{
    return [
        'call_id' => 1,
        'direction' => 'inbound',
        'caller' => '0563022929',
        'did' => '0237918911',
        'start' => '2026-11-17 09:51:22',
        'end' => '2026-11-17 09:54:18',
        'flow' => [
            ['segment_type' => 'ping', 'from_type' => 'world', 'from_dn' => '0563022929', 'to_type' => 'world', 'to_dn' => '0237918911', 'duration' => 1, 'started_at' => '2026-11-17 09:51:22', 'ended_at' => '2026-11-17 09:51:22', 'answered_at' => null, 'action' => "redirige l'utilisateur vers"],
            ['segment_type' => 'ping', 'from_type' => 'world', 'from_dn' => '0237918911', 'to_type' => 'extension', 'to_dn' => '101', 'to_name' => 'VIALLIS Veronika', 'duration' => 44, 'started_at' => '2026-11-17 09:51:22', 'ended_at' => '2026-11-17 09:52:06', 'answered_at' => '2026-11-17 09:51:50', 'action' => "redirige l'utilisateur vers"],
            ['segment_type' => 'pong', 'from_type' => 'world', 'from_dn' => '0237918911', 'to_type' => 'extension', 'to_dn' => '101', 'to_name' => 'VIALLIS Veronika', 'duration' => 16, 'started_at' => '2026-11-17 09:51:50', 'ended_at' => '2026-11-17 09:52:06', 'answered_at' => '2026-11-17 09:51:50', 'answered' => true, 'action' => ''],
            ['segment_type' => 'ping', 'from_type' => 'extension', 'from_dn' => '101', 'to_type' => 'queue', 'to_dn' => '800', 'to_name' => 'PIECES_RECHANGES', 'duration' => 131, 'started_at' => '2026-11-17 09:52:06', 'ended_at' => '2026-11-17 09:54:18', 'answered_at' => '2026-11-17 09:52:06', 'action' => "terminé par la source"],
        ],
    ];
}

it('shows the filtered queue result and who answered the call instead', function () {
    Livewire::test(CallAnalysis::class)
        ->set('filtersApplied', true)
        ->set('calls', [cardsCall()])
        ->set('pbxMap', ['extensions' => ['101' => 'VIALLIS Veronika'], 'call_queues' => ['800' => ['name' => 'PIECES_RECHANGES']]])
        ->set('loadedFilters', ['destination_dn' => '800', 'destination_dn_type' => '4', 'call_status' => 'unanswered'])
        ->assertSee('Not answered by PIECES_RECHANGES')
        ->assertSee('Answered by VIALLIS Veronika')
        ->assertSee('0563022929 called the line 0237918911')
        ->assertSee('In Queue')
        ->assertDontSee('Ping')
        ->assertDontSee("redirige l'utilisateur vers");
});

it('shows the call as answered by the filtered user', function () {
    Livewire::test(CallAnalysis::class)
        ->set('filtersApplied', true)
        ->set('calls', [cardsCall()])
        ->set('pbxMap', ['extensions' => ['101' => 'VIALLIS Veronika'], 'call_queues' => []])
        ->set('loadedFilters', ['destination_dn' => '101', 'destination_dn_type' => '0'])
        ->assertSee('Answered by VIALLIS Veronika')
        ->assertDontSee('Not answered by');
});
