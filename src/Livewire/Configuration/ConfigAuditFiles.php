<?php

namespace CXEngine\ExpertStatistics\Livewire\Configuration;

use CXEngine\ExpertStatistics\Concerns\ChecksExpertStatisticsModifyPermission;
use CXEngine\ExpertStatistics\Exceptions\AiFeaturesNotActivatedException;
use CXEngine\ExpertStatistics\Exceptions\ConfigAuditFileRefusedException;
use CXEngine\ExpertStatistics\Services\ExpertStatisticsService;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * "AI Audit files" tab of the PBX settings: what the AI Audit is computed
 * from, and the files an administrator can provide for it — a 3CX backup
 * XML when the backups are not collected, the Call Flow Designer sources
 * of each Call Flow App (replaced whenever the Call Flow changes), and the
 * audio prompts, transcribed so the audit knows what the messages say.
 *
 * The backend checks every file (type, structure, sizes) before keeping it;
 * uploading or removing requires expert-statistics.modify.
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
        $this->validate(['backupFile' => 'required|file|max:51200']);

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
        $this->validate(['promptsFile' => 'required|file|max:20480']);

        $this->upload('prompts', $this->promptsFile, [], 'prompts_uploaded');
        $this->promptsFile = null;
    }

    public function render(): View
    {
        return view('expert-statistics::livewire.configuration.config-audit-files');
    }

    private function upload(string $kind, TemporaryUploadedFile $file, array $fields, string $success): void
    {
        $this->attempt(
            fn (ExpertStatisticsService $service) => $service->uploadConfigAuditFile($kind, $file->getRealPath(), $file->getClientOriginalName(), $fields),
            $success,
        );
        $this->load();
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
