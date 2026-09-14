<?php

namespace CXEngine\ExpertStatistics\Livewire\Reports;

use CXEngine\ExpertStatistics\Concerns\AuthorizesExpertStatisticsAccess;
use CXEngine\ExpertStatistics\Concerns\ChecksExpertStatisticsModifyPermission;
use CXEngine\ExpertStatistics\Concerns\HasConfirmation;
use CXEngine\ExpertStatistics\Exceptions\NoActivePbxHostException;
use CXEngine\ExpertStatistics\Services\ExpertStatisticsService;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Throwable;

/**
 * Scheduled-reports list/edit/delete page, ported from bluerocktelclients'
 * App\Filament\Resources\Dashboard\Pages\PbxReports. A "scheduled" report is any
 * `reports` record with repeat=1 — one-off "sent" reports (repeat=0, created via
 * ShareReportModal's "Send" action) are intentionally excluded from this list.
 *
 * Unlike ShareReportModal (which only ever creates reports), this page can also
 * edit an existing schedule's name/recipients/element filter, and delete it. There
 * is no local Job/Mailable/queue infra involved anywhere — cron dispatch and email
 * sending happen entirely server-side; this page is CRUD-only against the `reports`
 * resource via ExpertStatisticsService.
 *
 * A normal permission-gated page (AuthorizesExpertStatisticsAccess only, same as
 * ManagePbxSettings) — not gated behind RequiresExpertStatisticsActivation like the
 * detailed report pages. Viewable by anyone with plain expert-statistics.view
 * access; editing or deleting a report requires expert-statistics.modify (see
 * canModify()/ensureCanModify(), ChecksExpertStatisticsModifyPermission).
 */
class ManageScheduledReports extends Component
{
    use AuthorizesExpertStatisticsAccess;
    use ChecksExpertStatisticsModifyPermission;
    use HasConfirmation;

    // ── Shared feedback banner ───────────────────────────────────────────────
    public ?string $statusType = null; // success|error|warning

    public ?string $statusMessage = null;

    // ── List state ────────────────────────────────────────────────────────────

    public string $search = '';

    public int $page = 1;

    /** @var array<int, array<string, mixed>> */
    public array $reportRows = [];

    /** @var array<string, int> */
    public array $pagination = [
        'current_page' => 1,
        'last_page' => 1,
        'total' => 0,
        'from' => 0,
        'to' => 0,
        'per_page' => 15,
    ];

    /** @var array<int, array{url: string|null, label: string, active: bool}> */
    public array $paginationLinks = [];

    // ── Edit panel state ──────────────────────────────────────────────────────

    public bool $showEditPanel = false;

    public ?string $editingReportId = null;

    /** @var array<string, mixed> */
    public array $editForm = [
        'name' => '',
        'emails' => [],
        'newEmail' => '',
        'dns' => [],
        'resource_group_id' => '',
        'element_type' => null,
        'report_type' => null,
    ];

    /** @var array<int, array<string, mixed>> */
    public array $editResourceGroups = [];

    /** @var array<string, mixed> */
    public array $pbxMap = [];

    public function mount(): void
    {
        $this->loadReports();
    }

    // ── Lifecycle / list actions ─────────────────────────────────────────────

    public function updatedSearch(): void
    {
        $this->page = 1;
        $this->loadReports();
    }

    public function changePage(int $page): void
    {
        $this->page = $page;
        $this->loadReports();
    }

    public function loadReports(): void
    {
        if ($this->activeHostName() === null) {
            return;
        }

        try {
            $params = ['page' => $this->page, 'filter[repeat]' => 1];
            if ($this->search !== '') {
                $params['filter[name]'] = $this->search;
            }

            $raw = $this->service()->getReports($params);

            $this->reportRows = $raw['data'] ?? [];
            $this->pagination = [
                'current_page' => (int) ($raw['current_page'] ?? 1),
                'last_page' => (int) ($raw['last_page'] ?? 1),
                'total' => (int) ($raw['total'] ?? 0),
                'from' => (int) ($raw['from'] ?? 0),
                'to' => (int) ($raw['to'] ?? 0),
                'per_page' => (int) ($raw['per_page'] ?? 15),
            ];
            $this->paginationLinks = $raw['links'] ?? [];
        } catch (Throwable) {
            $this->reportRows = [];
        }
    }

    public function deleteReport(string $id): void
    {
        $this->ensureCanModify();

        try {
            $this->service()->deleteReport($id);
            $this->loadReports();
            $this->flashSuccess('deleted');
        } catch (Throwable) {
            $this->flashError('deleteFailed');
        }
    }

    // ── Edit panel actions ────────────────────────────────────────────────────

    public function openEditReport(string $id): void
    {
        $this->ensureCanModify();

        $report = collect($this->reportRows)->firstWhere('id', $id);
        if ($report === null) {
            $this->flashWarning('notFound');

            return;
        }

        $rawEmails = array_filter(array_map('trim', explode(',', (string) ($report['email'] ?? ''))));
        $rawDns = array_filter(array_map('trim', explode(',', (string) ($report['dns'] ?? ''))));

        $this->editingReportId = $id;
        $this->editForm = [
            'name' => $report['name'] ?? '',
            'emails' => array_values($rawEmails),
            'newEmail' => '',
            'dns' => array_values($rawDns),
            'resource_group_id' => (string) ($report['pbx3cx_host_resource_group_id'] ?? ''),
            'element_type' => $report['element_type'] ?? null,
            'report_type' => $report['report_type'] ?? null,
        ];

        $this->loadEditSupportData($report);
        $this->showEditPanel = true;
    }

    public function cancelEditReport(): void
    {
        $this->showEditPanel = false;
        $this->editingReportId = null;
        $this->editForm = [
            'name' => '',
            'emails' => [],
            'newEmail' => '',
            'dns' => [],
            'resource_group_id' => '',
            'element_type' => null,
            'report_type' => null,
        ];
        $this->editResourceGroups = [];
        $this->pbxMap = [];
    }

    public function addEmailToReport(): void
    {
        $email = trim((string) ($this->editForm['newEmail'] ?? ''));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->flashWarning('invalidEmail');

            return;
        }

        if (in_array($email, $this->editForm['emails'], true)) {
            $this->flashWarning('emailDuplicate');

            return;
        }

        $this->editForm['emails'][] = $email;
        $this->editForm['newEmail'] = '';
    }

    public function removeEmailFromReport(string $email): void
    {
        $this->editForm['emails'] = array_values(
            array_filter($this->editForm['emails'], fn ($e) => $e !== $email)
        );
    }

    /**
     * Livewire lifecycle hook — fires when the resource group select changes via
     * wire:model.live. Populates dns from the group's resources automatically.
     */
    public function updatedEditFormResourceGroupId(string $groupId): void
    {
        if ($groupId === '') {
            return;
        }

        $group = collect($this->editResourceGroups)->firstWhere('id', (int) $groupId);
        if ($group === null) {
            return;
        }

        $resources = $group['resources'] ?? [];
        if (is_string($resources)) {
            $resources = json_decode($resources, true) ?? [];
        }

        $this->editForm['dns'] = array_values(array_map('strval', $resources));
    }

    public function toggleDnsElement(string $value): void
    {
        $this->editForm['resource_group_id'] = '';
        $dns = $this->editForm['dns'];
        $idx = array_search($value, $dns, true);
        if ($idx !== false) {
            array_splice($dns, $idx, 1);
        } else {
            $dns[] = $value;
        }
        $this->editForm['dns'] = array_values($dns);
    }

    public function saveEditReport(): void
    {
        $this->ensureCanModify();

        if (empty($this->editForm['emails'])) {
            $this->flashWarning('noRecipients');

            return;
        }

        $payload = [
            'name' => $this->editForm['name'],
            'email' => implode(',', $this->editForm['emails']),
            'dns' => implode(',', $this->editForm['dns']),
            'pbx3cx_host_resource_group_id' => $this->editForm['resource_group_id'] !== ''
                ? (int) $this->editForm['resource_group_id']
                : null,
        ];

        try {
            $this->service()->updateReport((string) $this->editingReportId, $payload);
            $this->cancelEditReport();
            $this->loadReports();
            $this->flashSuccess('saved');
        } catch (Throwable) {
            $this->flashError('saveFailed');
        }
    }

    public function render(): View
    {
        return view('expert-statistics::livewire.reports.manage-scheduled-reports');
    }

    /**
     * Public — called directly from the Blade view (`$this->activeHostName()`) to
     * gate the page behind "no PBX host configured", same convention as
     * ManagePbxSettings::activeHostName().
     */
    public function activeHostName(): ?string
    {
        try {
            return $this->service()->hostName();
        } catch (NoActivePbxHostException) {
            return null;
        }
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function service(): ExpertStatisticsService
    {
        return app(ExpertStatisticsService::class);
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function loadEditSupportData(array $report): void
    {
        if ($this->activeHostName() === null) {
            return;
        }

        try {
            if (empty($this->editResourceGroups)) {
                $allGroups = $this->service()->getResourceGroups();
                $elementType = $report['element_type'] ?? null;
                $this->editResourceGroups = $elementType !== null
                    ? array_values(array_filter($allGroups, fn ($g) => (int) ($g['type'] ?? -1) === (int) $elementType))
                    : $allGroups;
            }

            if (empty($this->pbxMap)) {
                $this->pbxMap = $this->service()->getMap();
            }
        } catch (Throwable) {
        }
    }

    private function flashSuccess(string $key): void
    {
        $this->statusType = 'success';
        $this->statusMessage = __("expert-statistics::pbx.reports.{$key}");
    }

    private function flashWarning(string $key): void
    {
        $this->statusType = 'warning';
        $this->statusMessage = __("expert-statistics::pbx.reports.{$key}");
    }

    private function flashError(string $key): void
    {
        $this->statusType = 'error';
        $this->statusMessage = __("expert-statistics::pbx.reports.{$key}");
    }
}
