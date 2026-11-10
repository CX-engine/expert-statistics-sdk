<?php

namespace CXEngine\ExpertStatistics\Livewire\Configuration;

use CXEngine\ExpertStatistics\Concerns\ChecksExpertStatisticsModifyPermission;
use CXEngine\ExpertStatistics\Exceptions\AiFeaturesNotActivatedException;
use CXEngine\ExpertStatistics\Exceptions\AuditUploadRejected;
use CXEngine\ExpertStatistics\Exceptions\ConfigAuditFileRefusedException;
use CXEngine\ExpertStatistics\Services\ExpertStatisticsService;
use CXEngine\ExpertStatistics\Support\AuditUploadInspector;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * "AI Audit files" tab of the PBX settings: what the AI Audit is computed
 * from, and the files an administrator can provide for it.
 *
 * The administrator drops the 3CX backup itself (.zip of any size, its
 * extracted folder, or the configuration .xml alone): the browser reads it
 * locally (resources/js/backup-reader.js), keeps only the configuration XML
 * (credentials blanked) and the audio prompts, then sends them through
 * uploadPrompts() (in batches under the upload limit) and uploadBackup().
 * Call tables are never read: the call statistics come from ClickHouse.
 *
 * Every file is checked here (AuditUploadInspector) before it is forwarded,
 * then again by the API. The Call Flow Designer sources upload is hidden
 * (config expert-statistics-api.config_audit.call_flow_upload) but kept.
 * Uploading or removing requires expert-statistics.modify.
 */
class ConfigAuditFiles extends Component
{
    use ChecksExpertStatisticsModifyPermission;
    use WithFileUploads;

    /** ok | not_activated | error */
    public string $state = 'ok';

    /** @var array<string, mixed> */
    public array $files = [];

    public ?TemporaryUploadedFile $backupFile = null;

    public ?TemporaryUploadedFile $callFlowFile = null;

    public string $callFlowApp = '';

    public ?TemporaryUploadedFile $promptsFile = null;

    public ?string $statusType = null;

    public ?string $statusMessage = null;

    public function mount(): void
    {
        $this->load();
    }

    public function uploadBackup(): void
    {
        $this->ensureCanModify();
        $this->validate(['backupFile' => 'required|file|max:51200'], $this->fileMessages('backupFile', 51200));

        $this->upload('backup', $this->backupFile, [], 'backup_uploaded');
        $this->backupFile = null;
    }

    public function uploadCallFlow(): void
    {
        $this->ensureCanModify();
        $this->validate([
            'callFlowApp' => 'required|string',
            'callFlowFile' => 'required|file|max:20480',
        ]);

        $user = auth()->user();
        $this->upload('call_flow', $this->callFlowFile, ['app' => $this->callFlowApp, 'uploaded_by' => $user?->name ?? $user?->email], 'call_flow_uploaded');
        $this->callFlowFile = null;
    }

    public function deleteCallFlow(string $app): void
    {
        $this->ensureCanModify();

        $this->attempt(fn (ExpertStatisticsService $service) => $service->deleteConfigAuditCallFlow($app), 'call_flow_deleted');
        $this->load();
    }

    public function uploadPrompts(): void
    {
        $this->ensureCanModify();
        $this->validate(['promptsFile' => 'required|file|max:20480'], $this->fileMessages('promptsFile', 20480));

        $this->upload('prompts', $this->promptsFile, [], 'prompts_uploaded');
        $this->promptsFile = null;
    }

    /**
     * Last step of a backup read in the browser: one message for the whole import.
     */
    public function backupImported(int $prompts): void
    {
        $this->ensureCanModify();
        if ($this->statusType === 'success') {
            $this->statusMessage = __('expert-statistics::pbx.config.auditFiles.backup_imported', ['count' => max(0, $prompts)]);
        }
    }

    public function callFlowUploadEnabled(): bool
    {
        return (bool) config('expert-statistics-api.config_audit.call_flow_upload', false);
    }

    /**
     * Limits given to the browser reader: one upload must fit the host's Livewire
     * temporary-upload limit (12 MB by default) and the API limits.
     *
     * @return array{batchBytes: int, maxXmlBytes: int, maxXmlMb: int}
     */
    public function readerLimits(): array
    {
        $livewireKb = 12288;
        foreach (FileUploadConfiguration::rules() as $rule) {
            if (is_string($rule) && preg_match('/^max:(\d+)$/', $rule, $m)) {
                $livewireKb = (int) $m[1];
            }
        }
        $livewireBytes = $livewireKb * 1024;
        $maxXml = min($livewireBytes, AuditUploadInspector::MAX_XML_BYTES);

        return [
            // 1 MB of margin for the multipart overhead.
            'batchBytes' => max(1048576, min($livewireBytes, AuditUploadInspector::MAX_PROMPTS_ZIP_BYTES) - 1048576),
            'maxXmlBytes' => $maxXml,
            'maxXmlMb' => intdiv($maxXml, 1048576),
        ];
    }

    public function render(): View
    {
        return view('expert-statistics::livewire.configuration.config-audit-files');
    }

    private function upload(string $kind, TemporaryUploadedFile $file, array $fields, string $success): void
    {
        // Nothing is forwarded before the file has been checked: the Livewire upload
        // endpoint can be called without the page's browser-side reading.
        try {
            match ($kind) {
                'backup' => app(AuditUploadInspector::class)->inspectConfiguration($file->getRealPath(), $file->getClientOriginalName()),
                'prompts' => app(AuditUploadInspector::class)->inspectPrompts($file->getRealPath(), $file->getClientOriginalName()),
                default => null,
            };
        } catch (AuditUploadRejected $rejected) {
            $file->delete();
            [$this->statusType, $this->statusMessage] = ['error', $rejected->getMessage()];

            return;
        }

        $this->attempt(
            fn (ExpertStatisticsService $service) => $service->uploadConfigAuditFile($kind, $file->getRealPath(), $file->getClientOriginalName(), $fields),
            $success,
        );
        $this->load();
    }

    /**
     * @return array<string, string>
     */
    private function fileMessages(string $field, int $maxKb): array
    {
        $t = fn (string $key, array $replace = []) => __("expert-statistics::pbx.config.auditFiles.errors.{$key}", $replace);

        return [
            "{$field}.required" => $t('missing'),
            "{$field}.file" => $t('unreadable'),
            "{$field}.uploaded" => $t('unreadable'),
            "{$field}.max" => $t('too_big', ['max' => intdiv($maxKb, 1024)]),
        ];
    }

    private function load(): void
    {
        try {
            $this->files = app(ExpertStatisticsService::class)->getConfigAuditFiles();
            $this->state = 'ok';
        } catch (AiFeaturesNotActivatedException) {
            $this->state = 'not_activated';
        } catch (\Throwable) {
            $this->state = 'error';
        }
    }

    private function attempt(\Closure $call, string $success): void
    {
        try {
            $call(app(ExpertStatisticsService::class));
            [$this->statusType, $this->statusMessage] = ['success', __("expert-statistics::pbx.config.auditFiles.{$success}")];
        } catch (ConfigAuditFileRefusedException $e) {
            [$this->statusType, $this->statusMessage] = ['error', __('expert-statistics::pbx.config.auditFiles.refused', ['reason' => $e->getMessage()])];
        } catch (\Throwable) {
            [$this->statusType, $this->statusMessage] = ['error', __('expert-statistics::pbx.expert_statistics.error_loading')];
        }
    }
}
