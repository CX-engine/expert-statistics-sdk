<?php

use CXEngine\ExpertStatistics\Contracts\ChecksExpertStatisticsActivation;
use CXEngine\ExpertStatistics\Livewire\ConfigAudit\ConfigAudit;
use CXEngine\ExpertStatistics\Tests\TestCase;
use Illuminate\Foundation\Auth\User as GenericUser;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

uses(TestCase::class);

function auditFinding(string $code, string $category, string $object, float $impact, array $extra = []): array
{
    return [
        'id' => "{$code}:{$object}",
        'category' => $category,
        'code' => $code,
        'area' => 'queues',
        'did' => ['0155878000'],
        'object' => $object,
        'finding' => "Finding about {$object}.",
        'proposed_action' => "Change it: 3CX admin › Call Queues › Support ({$object}).",
        'evidence' => 'Queue/MasterTimeout = 600 s.',
        'stats' => ['calls' => 120, 'abandon_rate' => 0.225, 'period' => '2026-09-14/2026-10-14'],
        'score_impact' => $impact,
        ...$extra,
    ];
}

function auditSummary(array $overrides = []): array
{
    return [
        'audit' => ['id' => 7, 'backup_file' => 'kuoni.xml', 'backup_date' => '2026-10-13 19:00:00', 'pbx_version' => '20.0.9.995'],
        'period' => ['start' => '2026-09-14', 'end' => '2026-10-14'],
        'mode' => 'statistics',
        'score' => 78.9,
        'areas' => [
            'routing' => ['score' => 92.5, 'findings' => 3],
            'queues' => ['score' => 64.2, 'findings' => 8],
            'hours' => ['score' => 99.0, 'findings' => 1],
            'call_flow_apps' => ['score' => 100.0, 'findings' => 0],
            'general' => ['score' => 100.0, 'findings' => 0],
        ],
        'coverage' => 0.67,
        'total_inbound_calls' => 12840,
        'counts' => ['by_category' => ['probable_error' => 2, 'to_check' => 6, 'optimisation' => 4]],
        'missing_inputs' => ['cfd_runtime_destinations:899', 'cfd_runtime_destinations:900', 'cfd_sources:menu.Main'],
        'top_recommendations' => [auditFinding('none_after_long_wait', 'to_check', 'queue:8000', 6.6)],
        'trend' => [['id' => 6, 'backup_date' => '2026-10-12', 'score' => 75.0], ['id' => 7, 'backup_date' => '2026-10-13', 'score' => 78.9]],
        ...$overrides,
    ];
}

function mockAudit(array $responses = []): MockClient
{
    return MockClient::global([
        '*config-audit/findings*' => MockResponse::make([
            'data' => [
                auditFinding('none_after_long_wait', 'to_check', 'queue:8000', 6.6),
                auditFinding('agent_unanswered_rings', 'to_check', 'extension:202', 1.2, ['agents' => ['202']]),
            ],
            'meta' => ['total' => 42, 'page' => 1, 'per_page' => 20, 'last_page' => 3],
        ], 200),
        '*config-audit/routes/*' => MockResponse::make([
            'route' => [
                'did' => '0155878000', 'rule_name' => 'Standard', 'trunk' => '10001', 'is_default' => false, 'covered_dids' => [],
                'branches' => ['all' => 'queue:8000'],
                'nodes' => [
                    ['id' => 'inbound_rule:0155878000', 'type' => 'inbound_rule', 'label' => 'Règle entrante / SDA 0155878000', 'details' => ['hours' => '24/7'], 'next' => [['condition' => 'all', 'to' => 'queue:8000']]],
                    ['id' => 'queue:8000', 'type' => 'queue', 'label' => 'File 8000 – Standard', 'details' => ['strategy' => 'Ring all', 'members' => [['dn' => '100', 'name' => 'Alice', 'status' => 'LoggedIn']]], 'next' => [['condition' => 'after max wait 600 s', 'to' => 'end_of_call']]],
                    ['id' => 'end_of_call', 'type' => 'end_of_call', 'label' => "Fin d'appel", 'details' => [], 'next' => []],
                ],
            ],
            'calls' => 9100,
            'findings' => [auditFinding('none_after_long_wait', 'to_check', 'queue:8000', 6.6)],
        ], 200),
        '*config-audit/routes*' => MockResponse::make([
            'data' => [['did' => '0155878000', 'rule_name' => 'Standard', 'trunk' => '10001', 'is_default' => false, 'covered_dids' => null,
                'first_destination' => ['id' => 'queue:8000', 'label' => 'File 8000 – Standard'], 'calls' => 9100, 'findings' => 3]],
            'meta' => ['total' => 1, 'page' => 1, 'per_page' => 20, 'last_page' => 1],
        ], 200),
        '*config-audit/queues*' => MockResponse::make(['data' => [[
            'number' => '8000', 'name' => 'Standard', 'strategy' => 'Ring all', 'department' => 'Accueil',
            'members' => [['dn' => '100', 'name' => 'Alice', 'status' => 'LoggedIn'], ['dn' => '101', 'name' => 'Bob', 'status' => 'LoggedOut']],
            'agents' => 2, 'agents_logged_in' => 1, 'ring_time' => 5, 'max_wait' => 600, 'wrap_up' => 15, 'overflow_label' => "Fin d'appel",
            'hours' => '24/7', 'callback' => false,
            'stats' => ['offered' => 9100, 'answered' => 8000, 'abandoned' => 900, 'abandoned_preanswer' => 0, 'avg_wait' => 35.0, 'answer_rate' => 0.879, 'abandon_rate' => 0.099],
            'findings' => [['code' => 'none_after_long_wait', 'category' => 'to_check', 'score_impact' => 6.6]], 'score_impact' => 6.6,
        ]]], 200),
        '*config-audit/agents*' => MockResponse::make([
            'data' => [['dn' => '202', 'name' => 'Carol', 'enabled' => true, 'current_profile' => 'Available', 'queue_status' => 'LoggedIn', 'queues_count' => 2,
                'queues' => [['queue' => '8000', 'name' => 'Standard', 'status_at_backup' => 'LoggedIn', 'offered' => 300, 'answered' => 60, 'connected_hours' => 20.0]],
                'offered' => 300, 'answered' => 60, 'answer_rate' => 0.2,
                'findings' => [['code' => 'agent_unanswered_rings', 'category' => 'to_check', 'finding' => 'Carol answers 20 %.', 'score_impact' => 1.2]], 'score_impact' => 1.2]],
            'meta' => ['total' => 1, 'page' => 1, 'per_page' => 20, 'last_page' => 1],
        ], 200),
        '*config-audit/history*' => MockResponse::make(['data' => [
            ['id' => 7, 'backup_file' => 'kuoni.xml', 'backup_date' => '2026-10-13 19:00:00', 'pbx_version' => '20.0.9.995', 'score' => 78.9, 'mode' => 'statistics',
                'period' => ['start' => '2026-09-14', 'end' => '2026-10-14'], 'configuration_findings' => ['probable_error' => 0, 'to_check' => 5, 'optimisation' => 3]],
        ]], 200),
        '*config-audit/report*' => MockResponse::make("# Audit de configuration 3CX\n", 200, ['Content-Type' => 'text/markdown']),
        '*config-audit/refresh*' => MockResponse::make(auditSummary(['score' => 81.0]), 200),
        '*config-audit*' => MockResponse::make(auditSummary(), 200),
        ...$responses,
    ]);
}

beforeEach(function () {
    $this->useHostComponentDoubles();
    Blade::anonymousComponentPath(__DIR__.'/../../Doubles/views/filament', 'filament');

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
    $user->email = 'jane@example.com';
    $this->actingAs($user);
    Cache::flush();
});

afterEach(function () {
    MockClient::destroyGlobal();
});

it('shows the score, the area scores, what was not analysed and the main recommendations', function () {
    mockAudit();

    Livewire::test(ConfigAudit::class)
        ->assertSet('state', 'ok')
        ->assertSee('78.9')
        ->assertSee('Configuration + call statistics')
        ->assertSee('Queues & agents')
        ->assertSee('64.2 %')
        ->assertSee('67 %')
        ->assertSee('Destinations chosen by Call Flow Apps during the call (completed with the observed calls)')
        ->assertSee('899, 900')
        ->assertSee('Call ends after a long wait')
        ->assertSee('Change it: 3CX admin › Call Queues › Support (queue:8000).')
        ->assertSee('−6.6 pts', false);
});

it('draws the score per backup with the change since the first backup', function () {
    mockAudit(['*config-audit*' => MockResponse::make(auditSummary(['trend' => [
        ['id' => 5, 'backup_date' => '2026-10-11', 'score' => 70.2],
        ['id' => 6, 'backup_date' => '2026-10-12', 'score' => 75.0],
        ['id' => 7, 'backup_date' => '2026-10-13', 'score' => 78.9],
    ]]), 200)]);

    Livewire::test(ConfigAudit::class)
        ->assertSee('Score per backup')
        ->assertSee('+8.7 pts')
        ->assertSee('since the backup of')
        ->assertSee('(3 backups)')
        ->assertSeeHtml('<polyline')
        ->assertSee('78.9 %')
        ->assertSee('+3.9 pts vs previous backup');
});

it('explains that the trend needs a second backup', function () {
    mockAudit(['*config-audit*' => MockResponse::make(auditSummary(['trend' => [['id' => 7, 'backup_date' => '2026-10-13', 'score' => 78.9]]]), 200)]);

    Livewire::test(ConfigAudit::class)
        ->assertSee('The trend appears after the next backup is analysed.')
        ->assertDontSeeHtml('<polyline');
});

it('shows an empty state when no backup has been analysed yet', function () {
    mockAudit(['*config-audit*' => MockResponse::make(['error' => 'No configuration audit is available for this PBX yet.'], 404)]);

    Livewire::test(ConfigAudit::class)
        ->assertSet('state', 'empty')
        ->assertSee('No AI Audit yet')
        ->assertDontSee('Recommendations');
});

it('follows the AI activation of the host like the other AI pages', function () {
    mockAudit(['*config-audit*' => MockResponse::make(['error' => 'AI features are not activated for this host.'], 403)]);

    Livewire::test(ConfigAudit::class)
        ->assertSet('state', 'not_activated')
        ->assertSee('not activated for this PBX');
});

it('shows an error when the API fails', function () {
    mockAudit(['*config-audit*' => MockResponse::make(['error' => 'boom'], 500)]);

    Livewire::test(ConfigAudit::class)
        ->assertSet('state', 'error')
        ->assertSee('Error loading data.');
});

it('loads the recommendations with the filters and the period', function () {
    $mock = mockAudit();

    Livewire::test(ConfigAudit::class)
        ->set('startDate', '2026-09-01')
        ->set('endDate', '2026-09-30')
        ->call('applyPeriod')
        ->call('showFindings', 'to_check', 'queues')
        ->assertSet('tab', 'recommendations')
        ->assertSee('Agent answers few calls')
        ->assertSee('Page 1 of 3 (42)');

    $mock->assertSent(function ($request, $response) {
        $url = urldecode((string) $response->getPendingRequest()->getUri());

        return str_contains($url, '/test-host.on3cx.fr/config-audit/findings')
            && str_contains($url, 'category[0]=to_check')
            && str_contains($url, 'area[0]=queues')
            && str_contains($url, 'start_date=2026-09-01')
            && str_contains($url, 'end_date=2026-09-30');
    });
});

it('rejects an incomplete period', function () {
    mockAudit();

    Livewire::test(ConfigAudit::class)
        ->set('startDate', '2026-09-30')
        ->set('endDate', '2026-09-01')
        ->call('applyPeriod')
        ->assertHasErrors('period');
});

it('lists the DIDs and opens the routing of one', function () {
    mockAudit();

    Livewire::test(ConfigAudit::class)
        ->call('selectTab', 'routing')
        ->assertSee('File 8000 – Standard')
        ->assertSee('9 100')
        ->call('showRoute', '0155878000')
        ->assertSet('selectedDid', '0155878000')
        ->assertSee('after max wait 600 s')
        ->assertSee("Fin d'appel")
        ->assertSee('100 Alice (LoggedIn)')
        ->call('closeRoute')
        ->assertSet('selectedDid', null);
});

it('shows where the calls went after a Call Flow App choosing destinations during the call', function () {
    mockAudit(['*config-audit/routes/*' => MockResponse::make([
        'route' => [
            'did' => '0173300001', 'rule_name' => null, 'trunk' => '10000', 'is_default' => false, 'covered_dids' => [],
            'branches' => ['all' => 'call_flow_app:899'],
            'nodes' => [
                ['id' => 'call_flow_app:899', 'type' => 'call_flow_app', 'label' => 'Call Flow App supportv2', 'details' => [], 'next' => [
                    ['condition' => 'condition Tech', 'to' => 'computed:call_flow_app:899:Tech'],
                    ['condition' => 'always', 'to' => 'queue:809'],
                ]],
                ['id' => 'computed:call_flow_app:899:Tech', 'type' => 'unresolved', 'label' => 'Destination calculée par le script', 'details' => [], 'next' => []],
                ['id' => 'queue:809', 'type' => 'queue', 'label' => 'File 809 – SupportFR', 'details' => [], 'next' => []],
            ],
        ],
        'observed' => ['call_flow_app:899' => ['calls' => 766, 'observed_share' => 0.812, 'destinations' => [
            ['number' => '809', 'label' => 'File 809 – SupportFR', 'calls' => 554, 'in_script' => true],
            ['number' => '815', 'label' => '815', 'calls' => 62, 'in_script' => false],
        ]]],
        'calls' => 590,
        'findings' => [],
    ], 200)]);

    Livewire::test(ConfigAudit::class)
        ->call('selectTab', 'routing')
        ->call('showRoute', '0173300001')
        ->assertSee('Observed in the calls')
        ->assertSee('766 calls entered, 81 % left to a recorded destination')
        ->assertSee('554 calls')
        ->assertSee('chosen during the call');
});

it('shows the queues with their agents and statistics', function () {
    mockAudit();

    Livewire::test(ConfigAudit::class)
        ->call('selectTab', 'queues')
        ->assertSee('8000 – Standard')
        ->assertSee('Abandoned 9.9 %')
        ->assertSee('Ring time: 5 s');
});

it('shows the agents with their answer rate and findings', function () {
    $mock = mockAudit();

    Livewire::test(ConfigAudit::class)
        ->call('selectTab', 'agents')
        ->assertSee('202 Carol')
        ->assertSee('20 %')
        ->set('agentSearch', 'carol')
        ->call('showFindingsFor', '202')
        ->assertSet('tab', 'recommendations')
        ->assertSet('findingSearch', '202');

    $mock->assertSent(fn ($request, $response) => str_contains((string) $response->getPendingRequest()->getUri(), 'config-audit/agents?search=carol')
        || str_contains(urldecode((string) $response->getPendingRequest()->getUri()), 'search=carol'));
});

it('shows the audit history', function () {
    mockAudit();

    Livewire::test(ConfigAudit::class)
        ->call('selectTab', 'history')
        ->assertSee('kuoni.xml')
        ->assertSee('78.9 %');
});

it('re-runs the audit and forgets every cached read', function () {
    $mock = mockAudit();

    $component = Livewire::test(ConfigAudit::class)->assertSee('78.9');

    // The summary is cached: a second component does not call the API again.
    $before = count(array_filter($mock->getRecordedResponses(), fn ($r) => str_ends_with(parse_url((string) $r->getPendingRequest()->getUri(), PHP_URL_PATH), '/config-audit')));
    Livewire::test(ConfigAudit::class);
    $cached = count(array_filter($mock->getRecordedResponses(), fn ($r) => str_ends_with(parse_url((string) $r->getPendingRequest()->getUri(), PHP_URL_PATH), '/config-audit')));
    expect($cached)->toBe($before);

    $component->call('refresh');

    $mock->assertSent(fn ($request, $response) => $response->getPendingRequest()->getMethod()->value === 'POST'
        && str_contains((string) $response->getPendingRequest()->getUri(), '/config-audit/refresh'));

    $after = count(array_filter($mock->getRecordedResponses(), fn ($r) => str_ends_with(parse_url((string) $r->getPendingRequest()->getUri(), PHP_URL_PATH), '/config-audit')));
    expect($after)->toBe($before + 1);
});

it('downloads the Markdown report', function () {
    mockAudit();

    Livewire::test(ConfigAudit::class)
        ->call('downloadReport')
        ->assertFileDownloaded('config-audit-2026-10-13.md');
});
