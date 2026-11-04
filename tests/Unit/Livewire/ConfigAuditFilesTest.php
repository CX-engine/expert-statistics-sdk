<?php

use CXEngine\ExpertStatistics\Livewire\Configuration\ConfigAuditFiles;
use CXEngine\ExpertStatistics\Tests\TestCase;
use Illuminate\Foundation\Auth\User as GenericUser;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

uses(TestCase::class);

function auditFilesMock(array $responses = []): MockClient
{
    return MockClient::global([
        ...$responses,
        '*config-audit/files' => MockResponse::make([
            'backups' => [['file' => 'encom.xml', 'size' => 7069207, 'backup_date' => '2026-10-18T22:01:01', 'uploaded' => false]],
            'call_flows' => [['app' => '899', 'uploaded_by' => 'Cyrille', 'uploaded_at' => '2026-11-04 10:00:00']],
            'prompts' => ['count' => 3, 'transcribed' => 3],
            'call_flow_apps' => [['app' => '899', 'name' => 'Call Flow App supportv2'], ['app' => '855', 'name' => 'Call Flow App accueil']],
        ], 200),
    ]);
}

beforeEach(function () {
    Gate::before(fn () => true);
    $user = new GenericUser;
    $user->id = 1;
    $user->name = 'Jane';
    $this->actingAs($user);
    Cache::flush();
});

afterEach(function () {
    MockClient::destroyGlobal();
});

it('shows what the audit is computed from and which Call Flow sources are missing', function () {
    auditFilesMock();

    Livewire::test(ConfigAuditFiles::class)
        ->assertSee('from the backups')
        ->assertSee('Call Flow App supportv2')
        ->assertSee('Sources provided on')
        ->assertSee('Sources not provided')
        ->assertSee('3 messages, 3 transcribed');
});

it('uploads the Call Flow sources of an app', function () {
    $mock = auditFilesMock(['*config-audit/files/call-flows' => MockResponse::make(['call_flow' => ['app' => '855']], 201)]);

    Livewire::test(ConfigAuditFiles::class)
        ->set('callFlowApp', '855')
        ->set('callFlowFile', UploadedFile::fake()->create('accueil.zip', 10, 'application/zip'))
        ->call('uploadCallFlow')
        ->assertSet('statusType', 'success')
        ->assertSee('Call Flow sources uploaded.');

    $mock->assertSent(fn ($request, $response) => $response->getPendingRequest()->getMethod()->value === 'POST'
        && str_ends_with((string) $response->getPendingRequest()->getUri(), '/config-audit/files/call-flows'));
});

it('shows why the server refused a file', function () {
    auditFilesMock(['*config-audit/files/backup' => MockResponse::make(['error' => 'The file is not an XML document.'], 422)]);

    Livewire::test(ConfigAuditFiles::class)
        ->set('backupFile', UploadedFile::fake()->createWithContent('backup.xml', 'nope'))
        ->call('uploadBackup')
        ->assertSet('statusType', 'error')
        ->assertSee('File refused: The file is not an XML document.');
});

it('only lets users allowed to modify upload files', function () {
    auditFilesMock();
    app()->forgetInstance(\Illuminate\Contracts\Auth\Access\Gate::class);
    Gate::clearResolvedInstances();

    Livewire::test(ConfigAuditFiles::class)
        ->assertDontSee('Upload the sources')
        ->call('uploadPrompts')
        ->assertForbidden();
});
