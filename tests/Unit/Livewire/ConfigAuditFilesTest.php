<?php

use CXEngine\ExpertStatistics\Exceptions\AuditUploadRejected;
use CXEngine\ExpertStatistics\Livewire\Configuration\ConfigAuditFiles;
use CXEngine\ExpertStatistics\Support\AuditUploadInspector;
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
    config(['expert-statistics-api.config_audit.call_flow_upload' => true]);
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
        ->set('backupFile', UploadedFile::fake()->createWithContent('backup.xml', "<?xml version=\"1.0\"?>\n<PhoneSystem></PhoneSystem>\n"))
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

// ─── Backup read in the browser, files checked before they are forwarded ───

function auditXml(string $body = '<header><version>20.0.10.1716</version></header><AuthPassword></AuthPassword>'): string
{
    return "\u{FEFF}<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<PhoneSystem>{$body}</PhoneSystem>\n";
}

/**
 * @param  array<string, string>  $entries
 */
function promptsZip(array $entries = [], ?callable $tweak = null, int $compression = ZipArchive::CM_DEFLATE): string
{
    $path = tempnam(sys_get_temp_dir(), 'prompts-').'.zip';
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    foreach ($entries ?: ['Accueil.wav' => 'RIFF'.pack('V', 36).'WAVEfmt '.str_repeat("\0", 24), 'Callflows/supportv2/Predec.wav' => 'RIFF'.pack('V', 36).'WAVEfmt '.str_repeat("\1", 24)] as $name => $content) {
        $zip->addFromString($name, $content);
        $zip->setCompressionName($name, $compression);
    }
    if ($tweak) {
        $tweak($zip);
    }
    $zip->close();

    return $path;
}

function rejection(\Closure $inspect): ?string
{
    try {
        $inspect(app(AuditUploadInspector::class));
    } catch (AuditUploadRejected $rejected) {
        return $rejected->reason;
    }

    return null;
}

function xmlFile(string $content): string
{
    $path = tempnam(sys_get_temp_dir(), 'config-').'.xml';
    file_put_contents($path, $content);

    return $path;
}

it('hides the Call Flow sources upload by default but keeps it available', function () {
    auditFilesMock();

    Livewire::test(ConfigAuditFiles::class)
        ->assertDontSee('Call Flow App supportv2')
        ->assertDontSee('Upload the sources')
        ->assertSee('3 messages, 3 transcribed');

    config(['expert-statistics-api.config_audit.call_flow_upload' => true]);
    Livewire::test(ConfigAuditFiles::class)->assertSee('Call Flow App supportv2');
});

it('reads the backup in the browser and never binds the file inputs to Livewire', function () {
    auditFilesMock();

    $html = Livewire::test(ConfigAuditFiles::class)
        ->assertSee('Drop the 3CX backup (.zip) or the configuration file (.xml)')
        ->assertSee('choose the extracted backup folder')
        ->assertSee('Getting the 3CX backup')
        ->assertSee('Passwords are removed before sending')
        ->html();

    expect($html)->not->toContain('wire:model="backupFile"')
        ->and($html)->not->toContain('wire:model="promptsFile"')
        ->and($html)->toContain('webkitdirectory');
    expect(\CXEngine\ExpertStatistics\Support\BackupReaderScript::source())->toContain('expertStatsBackupReader');
});

it('gives the browser limits that fit the Livewire upload limit', function () {
    auditFilesMock();

    $default = Livewire::test(ConfigAuditFiles::class)->instance()->readerLimits();
    expect($default['batchBytes'])->toBe(11 * 1048576)->and($default['maxXmlBytes'])->toBe(12 * 1048576);

    config(['livewire.temporary_file_upload.rules' => ['required', 'file', 'max:102400']]);
    $raised = Livewire::test(ConfigAuditFiles::class)->instance()->readerLimits();
    expect($raised['batchBytes'])->toBe(19 * 1048576)->and($raised['maxXmlBytes'])->toBe(50 * 1048576);
});

it('forwards a checked configuration and prompts, then sums the import up', function () {
    $mock = auditFilesMock([
        '*config-audit/files/prompts' => MockResponse::make(['prompts' => 2], 201),
        '*config-audit/files/backup' => MockResponse::make(['stored' => 'x.xml'], 201),
    ]);

    Livewire::test(ConfigAuditFiles::class)
        ->set('promptsFile', UploadedFile::fake()->createWithContent('prompts-1.zip', file_get_contents(promptsZip())))
        ->call('uploadPrompts')
        ->assertSet('statusType', 'success')
        ->set('backupFile', UploadedFile::fake()->createWithContent('1337879628Db.xml', auditXml()))
        ->call('uploadBackup')
        ->assertSet('statusType', 'success')
        ->call('backupImported', 2)
        ->assertSee('Backup imported: configuration and 2 audio messages uploaded.');

    $mock->assertSent('*config-audit/files/prompts');
    $mock->assertSent('*config-audit/files/backup');
});

it('refuses a suspicious file before anything is forwarded', function () {
    $mock = auditFilesMock();

    Livewire::test(ConfigAuditFiles::class)
        ->set('backupFile', UploadedFile::fake()->createWithContent('1337879628Db.xml', "<?xml version=\"1.0\"?>\n<!DOCTYPE x [<!ENTITY e SYSTEM \"file:///etc/passwd\">]>\n<PhoneSystem>&e;</PhoneSystem>"))
        ->call('uploadBackup')
        ->assertSet('statusType', 'error')
        ->assertSee('This is not a valid 3CX configuration')
        ->set('promptsFile', UploadedFile::fake()->createWithContent('prompts-1.zip', file_get_contents(promptsZip(['../../evil.wav' => 'x']))))
        ->call('uploadPrompts')
        ->assertSet('statusType', 'error')
        ->assertSee('refused for security');

    $mock->assertNotSent('*config-audit/files/backup');
    $mock->assertNotSent('*config-audit/files/prompts');
});

it('checks the configuration file', function () {
    expect(rejection(fn ($i) => $i->inspectConfiguration(xmlFile(auditXml()), '1337879628Db.xml')))->toBeNull()
        ->and(rejection(fn ($i) => $i->inspectConfiguration(xmlFile(''), 'a.xml')))->toBe('empty')
        ->and(rejection(fn ($i) => $i->inspectConfiguration(xmlFile(auditXml()), 'a.php')))->toBe('not_xml')
        ->and(rejection(fn ($i) => $i->inspectConfiguration(xmlFile('<html><script>alert(1)</script></html>'), 'a.xml')))->toBe('not_3cx')
        ->and(rejection(fn ($i) => $i->inspectConfiguration(xmlFile(auditXml('<open>')), 'a.xml')))->toBe('not_3cx')
        ->and(rejection(fn ($i) => $i->inspectConfiguration(xmlFile(auditXml(str_repeat('<a/>', 5000).'<!ENTITY e "x">')), 'a.xml')))->toBe('not_3cx')
        ->and(rejection(fn ($i) => $i->inspectConfiguration(xmlFile(auditXml("<a>\0</a>")), 'a.xml')))->toBe('not_3cx');
});

it('checks every prompt of a batch', function () {
    $wav = 'RIFF'.pack('V', 36).'WAVEfmt '.str_repeat("\0", 24);

    expect(rejection(fn ($i) => $i->inspectPrompts(promptsZip(), 'prompts-1.zip')))->toBeNull()
        ->and(rejection(fn ($i) => $i->inspectPrompts(promptsZip(), 'prompts.exe')))->toBe('not_zip')
        ->and(rejection(fn ($i) => $i->inspectPrompts(xmlFile(random_bytes(2048)), 'p.zip')))->toBe('not_zip')
        ->and(rejection(fn ($i) => $i->inspectPrompts(xmlFile(substr(file_get_contents(promptsZip()), 0, -30)), 'p.zip')))->toBe('corrupted')
        ->and(rejection(fn ($i) => $i->inspectPrompts(promptsZip(['run.php' => '<?php echo 1;']), 'p.zip')))->toBe('unexpected_file')
        ->and(rejection(fn ($i) => $i->inspectPrompts(promptsZip(['fake.wav' => "MZ\x90\0 executable"]), 'p.zip')))->toBe('not_wave')
        ->and(rejection(fn ($i) => $i->inspectPrompts(promptsZip(['/etc/evil.wav' => $wav]), 'p.zip')))->toBe('unsafe_path')
        ->and(rejection(fn ($i) => $i->inspectPrompts(promptsZip(['a\\..\\evil.wav' => $wav]), 'p.zip')))->toBe('unsafe_path')
        ->and(rejection(fn ($i) => $i->inspectPrompts(promptsZip(['link.wav' => $wav], fn ($z) => $z->setExternalAttributesName('link.wav', ZipArchive::OPSYS_UNIX, 0120777 << 16)), 'p.zip')))->toBe('unsafe_path')
        ->and(rejection(fn ($i) => $i->inspectPrompts(promptsZip(['a.wav' => $wav], fn ($z) => $z->setEncryptionName('a.wav', ZipArchive::EM_AES_256, 'pw')), 'p.zip')))->toBe('encrypted')
        ->and(rejection(fn ($i) => $i->inspectPrompts(promptsZip(['bomb.wav' => 'RIFF'.str_repeat("\0", 15 * 1048576)]), 'p.zip')))->toBe('too_large_content');

    // Altered bytes: the CRC no longer matches.
    $content = file_get_contents(promptsZip(['a.wav' => $wav.'original-data'], null, ZipArchive::CM_STORE));
    $altered = xmlFile(str_replace('original-data', 'ALTERED-DATA!', $content));
    expect(rejection(fn ($i) => $i->inspectPrompts($altered, 'p.zip')))->toBe('corrupted');
});
