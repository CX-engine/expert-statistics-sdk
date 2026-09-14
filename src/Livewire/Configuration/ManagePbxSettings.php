<?php

namespace CXEngine\ExpertStatistics\Livewire\Configuration;

use CXEngine\ExpertStatistics\Concerns\AuthorizesExpertStatisticsAccess;
use CXEngine\ExpertStatistics\Concerns\ChecksExpertStatisticsModifyPermission;
use CXEngine\ExpertStatistics\Exceptions\NoActivePbxHostException;
use CXEngine\ExpertStatistics\Services\ExpertStatisticsService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Livewire\Attributes\Url;
use Livewire\Component;
use Throwable;

/**
 * PBX settings management page, ported from bluerocktelclients'
 * App\Filament\Resources\Dashboard\Pages\PbxConfiguration — every tab except
 * `pbx` (host selection stays in this app's own, more mature Pbx3cxHost CRUD,
 * see modules/ExpertStatistics/Livewire/Hosts/*).
 *
 * Deliberately does NOT use RequiresExpertStatisticsActivation: agent labels,
 * queue pre-answer times, report-table thresholds, AI alert settings, groups
 * and wallboards must stay configurable regardless of the host's trial/
 * subscription state.
 *
 * Viewable by anyone with plain expert-statistics.view access
 * (AuthorizesExpertStatisticsAccess) — a read-only client can browse every
 * tab. Actually changing something requires expert-statistics.modify (or
 * the broader wildcard): every method that persists a change calls
 * ensureCanModify() first, and the Blade views hide/disable the
 * save/create/delete controls for view-only users via canModify()
 * (ChecksExpertStatisticsModifyPermission).
 */
class ManagePbxSettings extends Component
{
    use AuthorizesExpertStatisticsAccess;
    use ChecksExpertStatisticsModifyPermission;

    #[Url]
    public string $tab = 'agent';

    // ── Shared feedback banner ───────────────────────────────────────────
    public ?string $statusType = null; // success|error|warning

    public ?string $statusMessage = null;

    // ── Agent tab ─────────────────────────────────────────────────────────
    /** @var array<string, mixed> */
    public array $agentForm = [
        'status_custom1' => null,
        'status_custom2' => null,
    ];

    public bool $agentConfigExists = false;

    // ── Queue tab ─────────────────────────────────────────────────────────
    /** @var array<string, mixed> */
    public array $queueForm = ['preanswer_seconds' => 0];

    /** @var array<int, string> */
    public array $selectedQueues = [];

    /** @var array<int, array<string, string>> */
    public array $availableQueues = [];

    /** @var array<int, array<string, mixed>> */
    public array $preanswerCurrent = [];

    /** @var array<string, mixed> */
    public array $preanswerHistory = [
        'data' => [],
        'current_page' => 1,
        'last_page' => 1,
    ];

    public string $queueSubTab = 'current';

    public string $queueSearch = '';

    /** @var array<int, int> */
    public array $selectedItems = [];

    // ── Report table tab ─────────────────────────────────────────────────
    public bool $reportTableConfigExists = false;

    /**
     * The backend keys each report-table config by a `customer_code` that is
     * expected to match the XP-Stats Customer record synced from this app's
     * Pbx3cxHost::code — but ExpertStatisticsService (and the
     * ResolvesActivePbxHost contract it depends on) only exposes the PBX
     * host name, not that code. Resolved from an existing config's own
     * `customer_code` when one is found (loadReportTableConfig()); left null
     * for a host with no config yet, in which case saveReportTableForm()
     * falls back to the host name as a stopgap. A future phase should expose
     * the host's real XP-Stats customer code through the service layer.
     */
    public ?string $reportTableCustomerCode = null;

    /** @var array<string, mixed> */
    public array $reportTableForm = [
        'initial_interval' => 20,
        'following_interval' => 20,
        'count_interval' => 4,
        'answered_percentage_red_value' => 0,
        'answered_percentage_orange_value' => 60,
        'answered_percentage_yellow_value' => 70,
        'answered_percentage_green_value' => 80,
        'answered_percentage_red_active' => false,
        'answered_percentage_orange_active' => false,
        'answered_percentage_yellow_active' => false,
        'answered_percentage_green_active' => false,
        'duration_avg_answer_red_value' => 60,
        'duration_avg_answer_orange_value' => 50,
        'duration_avg_answer_yellow_value' => 40,
        'duration_avg_answer_green_value' => 30,
        'duration_avg_answer_red_active' => false,
        'duration_avg_answer_orange_active' => false,
        'duration_avg_answer_yellow_active' => false,
        'duration_avg_answer_green_active' => false,
        'duration_avg_answer_user_red_value' => 60,
        'duration_avg_answer_user_orange_value' => 50,
        'duration_avg_answer_user_yellow_value' => 40,
        'duration_avg_answer_user_green_value' => 30,
        'duration_avg_answer_user_red_active' => false,
        'duration_avg_answer_user_orange_active' => false,
        'duration_avg_answer_user_yellow_active' => false,
        'duration_avg_answer_user_green_active' => false,
        'duration_avg_call_red_value' => 60,
        'duration_avg_call_orange_value' => 50,
        'duration_avg_call_yellow_value' => 40,
        'duration_avg_call_green_value' => 30,
        'duration_avg_call_red_active' => false,
        'duration_avg_call_orange_active' => false,
        'duration_avg_call_yellow_active' => false,
        'duration_avg_call_green_active' => false,
        'ratio_solicitations_red_value' => 1.5,
        'ratio_solicitations_orange_value' => 1.25,
        'ratio_solicitations_yellow_value' => 1.1,
        'ratio_solicitations_green_value' => 1.0,
        'ratio_solicitations_red_active' => false,
        'ratio_solicitations_orange_active' => false,
        'ratio_solicitations_yellow_active' => false,
        'ratio_solicitations_green_active' => false,
    ];

    // ── Groups tab ────────────────────────────────────────────────────────
    public string $groupsSubTab = 'queue';

    /** @var array<int, array<string, mixed>> */
    public array $groups = [];

    /** @var array<string, mixed> */
    public array $pbxMap = [];

    public bool $showGroupForm = false;

    public ?int $editingGroupId = null;

    /** @var array<string, mixed> */
    public array $groupForm = ['name' => '', 'type' => 4, 'resources' => []];

    public string $callerSearch = '';

    /** @var array<int, string> */
    public array $callerSearchResults = [];

    // ── AI alerts tab ─────────────────────────────────────────────────────
    /** @var array<string, mixed> */
    public array $aiAlertSettings = [
        'enabled' => true,
        'check_interval_minutes' => 60,
        'language' => 'fr',
        'notification_email' => null,
        'thresholds' => [
            'abandon_rate_warning' => 15,
            'abandon_rate_critical' => 30,
            'not_answered_rate_warning' => 10,
            'not_answered_rate_critical' => 20,
            'abandoned_preanswer_rate_warning' => 20,
            'wait_time_warning' => 60,
            'wait_time_critical' => 120,
            'volume_change_percent' => 50,
            'abandon_rate_change_percent' => 30,
        ],
    ];

    // ── Wallboard tab ─────────────────────────────────────────────────────
    /** @var array<int, array<string, mixed>> */
    public array $wallboards = [];

    public int $wallboardMax = 6;

    public ?string $selectedWallboardUuid = null;

    public bool $wallboardHasLiveData = true;

    /** @var array<int, array<string, mixed>> resolved tiles for the live preview */
    public array $previewTiles = [];

    /** @var array<string, mixed> */
    public array $previewMeta = [];

    /** @var array<int, array<string, mixed>> */
    public array $wallboardMetrics = [];

    /** @var array<int, array<string, mixed>> */
    public array $wallboardQueues = [];

    public bool $showWallboardForm = false;

    public ?string $editingWallboardUuid = null;

    public bool $editingIsDefault = false;

    /** @var array<string, mixed> */
    public array $wallboardForm = [
        'name' => '',
        'description' => '',
        'columns' => 3,
        'refresh_seconds' => 10,
        'tiles' => [],
    ];

    public ?string $newTileMetric = null;

    public ?string $newTileQueue = null;

    public string $wallboardPrompt = '';

    public bool $wallboardBuilding = false;

    /** @var array<string, mixed>|null generated, not-yet-saved AI result */
    public ?array $wallboardAiResult = null;

    public function mount(): void
    {
        $this->loadTabData();
    }

    public function updatedTab(): void
    {
        $this->statusMessage = null;
        $this->loadTabData();
    }

    public function switchTab(string $tab): void
    {
        $this->tab = $tab;
        $this->statusMessage = null;
        $this->loadTabData();
    }

    public function render(): View
    {
        return view('expert-statistics::livewire.configuration.manage-pbx-settings');
    }

    // ── Load dispatch ─────────────────────────────────────────────────────

    private function loadTabData(): void
    {
        match ($this->tab) {
            'agent' => $this->loadAgentConfig(),
            'queue' => $this->loadQueueData(),
            'report-table' => $this->loadReportTableConfig(),
            'ai-alerts' => $this->loadAiAlertSettings(),
            'groups' => $this->loadGroups(),
            'wallboard' => $this->loadWallboards(),
            default => null,
        };
    }

    private function service(): ExpertStatisticsService
    {
        return app(ExpertStatisticsService::class);
    }

    /**
     * Public — called directly from the Blade views (e.g. `$this->activeHostName()`)
     * to gate tab content behind "no PBX host configured", same as every other
     * `$this->`-called helper in this package's views (see formatDuration()/
     * getDisplayData() on the report components).
     */
    public function activeHostName(): ?string
    {
        try {
            return $this->service()->hostName();
        } catch (NoActivePbxHostException) {
            return null;
        }
    }

    private function currentUserIdentity(): string
    {
        $user = Auth::user();

        return (string) ($user?->email ?? $user?->name ?? 'unknown');
    }

    private function flashSuccess(string $key): void
    {
        $this->statusType = 'success';
        $this->statusMessage = __("expert-statistics::pbx.{$key}");
    }

    private function flashWarning(string $key): void
    {
        $this->statusType = 'warning';
        $this->statusMessage = __("expert-statistics::pbx.{$key}");
    }

    private function flashError(string $key): void
    {
        $this->statusType = 'error';
        $this->statusMessage = __("expert-statistics::pbx.{$key}");
    }

    // ── Agent tab ─────────────────────────────────────────────────────────

    private function loadAgentConfig(): void
    {
        if ($this->activeHostName() === null) {
            return;
        }

        try {
            $config = $this->service()->getAgentConfiguration();

            if (! empty($config)) {
                $this->agentConfigExists = true;
                $this->agentForm['status_custom1'] = $config['status_custom1'] ?? null;
                $this->agentForm['status_custom2'] = $config['status_custom2'] ?? null;
            }
        } catch (Throwable) {
        }
    }

    public function saveAgentConfig(): void
    {
        $this->ensureCanModify();

        if ($this->activeHostName() === null) {
            $this->flashWarning('config.noHost');

            return;
        }

        try {
            if ($this->agentConfigExists) {
                $this->service()->updateAgentConfiguration($this->agentForm);
            } else {
                $this->service()->createAgentConfiguration($this->agentForm);
                $this->agentConfigExists = true;
            }

            $this->flashSuccess('config.saved');
        } catch (Throwable) {
            $this->flashError('config.saveFailed');
        }
    }

    // ── Queue tab ─────────────────────────────────────────────────────────

    private function loadQueueData(): void
    {
        if ($this->activeHostName() === null) {
            $this->availableQueues = [];

            return;
        }

        try {
            $map = $this->service()->getMap();
            $queues = $map['call_queues'] ?? [];
            $this->availableQueues = array_values(array_map(
                fn (string $key, array $q): array => ['value' => $key, 'label' => "{$key} - {$q['name']}"],
                array_keys($queues),
                array_values($queues),
            ));
        } catch (Throwable) {
            $this->availableQueues = [];
        }

        $this->fetchPreanswerTimes();
    }

    public function updatedQueueSearch(): void
    {
        $this->preanswerHistory = ['data' => [], 'current_page' => 1, 'last_page' => 1];
        $this->fetchPreanswerTimes(1);
    }

    public function updatedQueueSubTab(): void
    {
        $this->selectedItems = [];
    }

    public function toggleSelectAll(): void
    {
        $ids = $this->queueSubTab === 'current'
            ? array_column($this->preanswerCurrent, 'id')
            : array_column($this->preanswerHistory['data'] ?? [], 'id');

        if (count($ids) > 0 && count(array_diff($ids, $this->selectedItems)) === 0) {
            $this->selectedItems = array_values(array_diff($this->selectedItems, $ids));
        } else {
            $this->selectedItems = array_values(array_unique(array_merge($this->selectedItems, $ids)));
        }
    }

    public function fetchPreanswerTimes(int $page = 1): void
    {
        if ($this->activeHostName() === null) {
            return;
        }

        try {
            $params = ['page' => $page];
            if ($this->queueSearch !== '') {
                $params['queue'] = $this->queueSearch;
            }

            $data = $this->service()->getPreanswerTimes($params);
            $this->preanswerCurrent = $data['current'] ?? [];
            $this->preanswerHistory = array_merge($this->preanswerHistory, $data['history'] ?? []);
        } catch (Throwable) {
        }
    }

    public function saveQueueConfig(): void
    {
        $this->ensureCanModify();

        if (empty($this->selectedQueues)) {
            $this->flashWarning('config.selectQueue');

            return;
        }

        if ($this->activeHostName() === null) {
            $this->flashWarning('config.noHost');

            return;
        }

        $labelMap = array_column($this->availableQueues, 'label', 'value');
        $createdBy = $this->currentUserIdentity();
        $configs = array_map(fn (string $q): array => [
            'queue' => $q,
            'label' => $labelMap[$q] ?? null,
            'preanswer_seconds' => (int) $this->queueForm['preanswer_seconds'],
            'created_by' => $createdBy,
        ], $this->selectedQueues);

        try {
            $this->service()->bulkCreatePreanswerTimes($configs);
            $this->selectedQueues = [];
            $this->fetchPreanswerTimes();
            $this->flashSuccess('config.saved');
        } catch (Throwable) {
            $this->flashError('config.saveFailed');
        }
    }

    public function deletePreanswerTime(int $id): void
    {
        $this->ensureCanModify();

        try {
            $this->service()->bulkDeletePreanswerTimes([$id]);
            $this->selectedItems = array_values(array_filter($this->selectedItems, fn ($i) => $i !== $id));
            $this->fetchPreanswerTimes();
            $this->flashSuccess('config.deleted');
        } catch (Throwable) {
            $this->flashError('config.saveFailed');
        }
    }

    public function bulkDeletePreanswerTimes(): void
    {
        $this->ensureCanModify();

        if (empty($this->selectedItems)) {
            return;
        }

        try {
            $this->service()->bulkDeletePreanswerTimes($this->selectedItems);
            $this->selectedItems = [];
            $this->fetchPreanswerTimes();
            $this->flashSuccess('config.deleted');
        } catch (Throwable) {
            $this->flashError('config.saveFailed');
        }
    }

    public function changePage(int $page): void
    {
        $this->fetchPreanswerTimes($page);
    }

    // ── Report table tab ─────────────────────────────────────────────────

    private function loadReportTableConfig(): void
    {
        $hostname = $this->activeHostName();
        if ($hostname === null) {
            return;
        }

        try {
            $configs = $this->service()->getReportTableConfigs();
            $config = collect($configs)->first(fn (array $c): bool => ($c['host_name'] ?? null) === $hostname);

            if ($config !== null) {
                $this->reportTableConfigExists = true;
                $this->reportTableCustomerCode = $config['customer_code'] ?? $hostname;
                $this->reportTableForm = array_merge($this->reportTableForm, $this->normalizeBooleans($config));
            } else {
                $this->reportTableConfigExists = false;
                $this->reportTableCustomerCode = null;
            }
        } catch (Throwable) {
            $this->reportTableConfigExists = false;
            $this->reportTableCustomerCode = null;
        }
    }

    public function saveReportTableForm(): void
    {
        $this->ensureCanModify();

        $hostname = $this->activeHostName();
        if ($hostname === null) {
            $this->flashWarning('config.noHost');

            return;
        }

        $customerCode = $this->reportTableCustomerCode ?? $hostname;
        $payload = array_merge($this->reportTableForm, [
            'host_name' => $hostname,
            'customer_code' => $customerCode,
        ]);

        try {
            if ($this->reportTableConfigExists) {
                $this->service()->updateReportTableConfig($customerCode, $payload);
            } else {
                $this->service()->createReportTableConfig($payload);
                $this->reportTableConfigExists = true;
                $this->reportTableCustomerCode = $customerCode;
            }

            $this->flashSuccess('config.saved');
        } catch (Throwable) {
            $this->flashError('config.saveFailed');
        }
    }

    public function activateAll(string $section): void
    {
        $this->ensureCanModify();

        foreach (['red', 'orange', 'yellow', 'green'] as $level) {
            $this->reportTableForm["{$section}_{$level}_active"] = true;
        }
    }

    public function deactivateAll(string $section): void
    {
        $this->ensureCanModify();

        foreach (['red', 'orange', 'yellow', 'green'] as $level) {
            $this->reportTableForm["{$section}_{$level}_active"] = false;
        }
    }

    // ── Groups tab ────────────────────────────────────────────────────────

    private function loadGroups(): void
    {
        if ($this->activeHostName() === null) {
            return;
        }

        try {
            $this->groups = $this->service()->getResourceGroups();

            if (empty($this->pbxMap)) {
                $this->pbxMap = $this->service()->getMap();
            }
        } catch (Throwable) {
            $this->groups = [];
        }
    }

    public function switchGroupsSubTab(string $subTab): void
    {
        $this->groupsSubTab = $subTab;
        $this->showGroupForm = false;
        $this->editingGroupId = null;
        $this->groupForm = ['name' => '', 'type' => $this->groupTypeForSubTab($subTab), 'resources' => []];
        $this->callerSearch = '';
        $this->callerSearchResults = [];
    }

    public function openCreateGroupForm(): void
    {
        $this->showGroupForm = true;
        $this->editingGroupId = null;
        $this->groupForm = ['name' => '', 'type' => $this->groupTypeForSubTab($this->groupsSubTab), 'resources' => []];
        $this->callerSearch = '';
        $this->callerSearchResults = [];
    }

    public function openEditGroupForm(int $id): void
    {
        $group = collect($this->groups)->firstWhere('id', $id);
        if ($group === null) {
            return;
        }

        $resources = $group['resources'] ?? [];
        if (is_string($resources)) {
            $resources = json_decode($resources, true) ?? [];
        }

        $resources = array_map('strval', $resources);

        $this->showGroupForm = true;
        $this->editingGroupId = $id;
        $this->groupForm = [
            'name' => $group['name'],
            'type' => (int) $group['type'],
            'resources' => $resources,
        ];
        $this->callerSearch = '';
        $this->callerSearchResults = [];
    }

    public function cancelGroupForm(): void
    {
        $this->showGroupForm = false;
        $this->editingGroupId = null;
        $this->groupForm = ['name' => '', 'type' => $this->groupTypeForSubTab($this->groupsSubTab), 'resources' => []];
        $this->callerSearch = '';
        $this->callerSearchResults = [];
    }

    public function saveGroup(): void
    {
        $this->ensureCanModify();

        if ($this->activeHostName() === null) {
            $this->flashWarning('config.groups.noHost');

            return;
        }

        $payload = [
            'name' => $this->groupForm['name'],
            'type' => (int) $this->groupForm['type'],
            'resources' => array_values($this->groupForm['resources'] ?? []),
        ];

        try {
            if ($this->editingGroupId !== null) {
                $this->service()->updateResourceGroup($this->editingGroupId, $payload);
            } else {
                $this->service()->createResourceGroup($payload);
            }

            $this->loadGroups();
            $this->showGroupForm = false;
            $this->editingGroupId = null;
            $this->groupForm = ['name' => '', 'type' => $this->groupTypeForSubTab($this->groupsSubTab), 'resources' => []];
            $this->callerSearch = '';
            $this->callerSearchResults = [];
            $this->flashSuccess('config.saved');
        } catch (Throwable) {
            $this->flashError('config.saveFailed');
        }
    }

    public function deleteGroup(int $id): void
    {
        $this->ensureCanModify();

        try {
            $this->service()->deleteResourceGroup($id);
            $this->groups = array_values(array_filter($this->groups, fn ($g) => $g['id'] !== $id));
            $this->flashSuccess('config.deleted');
        } catch (Throwable) {
            $this->flashError('config.saveFailed');
        }
    }

    public function updatedCallerSearch(): void
    {
        if (\strlen($this->callerSearch) < 2) {
            $this->callerSearchResults = [];

            return;
        }

        if ($this->activeHostName() === null) {
            return;
        }

        try {
            $results = $this->service()->searchCallerNumbers($this->callerSearch);
            $this->callerSearchResults = array_column($results['data'] ?? $results, 'caller_number');
        } catch (Throwable) {
            $this->callerSearchResults = [];
        }
    }

    public function addCallerResource(string $number): void
    {
        if (! \in_array($number, $this->groupForm['resources'], true)) {
            $this->groupForm['resources'][] = $number;
        }

        $this->callerSearch = '';
        $this->callerSearchResults = [];
    }

    public function removeCallerResource(string $number): void
    {
        $this->groupForm['resources'] = array_values(
            array_filter($this->groupForm['resources'], fn ($r) => $r !== $number)
        );
    }

    private function groupTypeForSubTab(string $subTab): int
    {
        return match ($subTab) {
            'extension' => 0,
            'did' => 1,
            'queue' => 4,
            'caller' => 99,
            default => 4,
        };
    }

    // ── AI alerts tab ─────────────────────────────────────────────────────

    private function loadAiAlertSettings(): void
    {
        if ($this->activeHostName() === null) {
            return;
        }

        try {
            $data = $this->service()->getAiAlertSettings();

            $thresholds = array_merge(
                $this->aiAlertSettings['thresholds'],
                $data['thresholds'] ?? [],
            );

            $this->aiAlertSettings = array_merge($this->aiAlertSettings, $data, ['thresholds' => $thresholds]);
        } catch (Throwable) {
        }
    }

    public function saveAiAlertSettings(): void
    {
        $this->ensureCanModify();

        if ($this->activeHostName() === null) {
            $this->flashWarning('config.noHost');

            return;
        }

        try {
            $this->service()->updateAiAlertSettings($this->aiAlertSettings);
            $this->flashSuccess('config.saved');
        } catch (Throwable) {
            $this->flashError('config.saveFailed');
        }
    }

    // ── Wallboard tab ─────────────────────────────────────────────────────

    private function loadWallboards(): void
    {
        if ($this->activeHostName() === null) {
            return;
        }

        $this->checkWallboardHasLiveData();

        try {
            $resp = $this->service()->getWallboards();
            $this->wallboards = $resp['data'] ?? [];
            $this->wallboardMax = (int) ($resp['max_per_host'] ?? 6);
        } catch (Throwable) {
            $this->wallboards = [];
        }

        try {
            $meta = $this->service()->getWallboardMeta([], ! $this->wallboardHasLiveData);
            $this->wallboardMetrics = $meta['metrics'] ?? [];
            $this->wallboardQueues = $meta['queues'] ?? [];
        } catch (Throwable) {
        }

        $uuids = array_column($this->wallboards, 'uuid');
        if ($this->selectedWallboardUuid === null || ! \in_array($this->selectedWallboardUuid, $uuids, true)) {
            $default = collect($this->wallboards)->firstWhere('is_default', true);
            $this->selectedWallboardUuid = $default['uuid'] ?? ($this->wallboards[0]['uuid'] ?? null);
        }

        $this->refreshWallboardPreview();
    }

    private function checkWallboardHasLiveData(): void
    {
        try {
            $resp = $this->service()->getWallboardHasData(false);
            $this->wallboardHasLiveData = (bool) ($resp['has_data'] ?? true);
        } catch (Throwable) {
            $this->wallboardHasLiveData = true;
        }
    }

    /** Live preview — polled from the Blade view. */
    public function refreshWallboardPreview(): void
    {
        if ($this->activeHostName() === null || $this->selectedWallboardUuid === null) {
            $this->previewTiles = [];
            $this->previewMeta = [];

            return;
        }

        if (! $this->wallboardHasLiveData) {
            $this->refreshWallboardPreviewAggregated();

            return;
        }

        try {
            $resp = $this->service()->getWallboard($this->selectedWallboardUuid);
            $wb = $resp['wallboard'] ?? [];
            $this->previewTiles = $resp['tiles'] ?? [];
            $this->previewMeta = [
                'name' => $wb['name'] ?? '',
                'description' => $wb['description'] ?? '',
                'layout' => $wb['layout'] ?? ['columns' => 3],
            ];
        } catch (Throwable) {
            $this->previewTiles = [];
        }
    }

    /**
     * Resolve tiles via the aggregated endpoint for hosts that have no live
     * data. Tile definitions come from the already-loaded wallboards list —
     * no extra round-trip to wallboards/{uuid} required, which would hit the
     * live service.
     */
    private function refreshWallboardPreviewAggregated(): void
    {
        $wb = collect($this->wallboards)->first(fn (array $w): bool => ($w['uuid'] ?? null) === $this->selectedWallboardUuid);
        if ($wb === null) {
            $this->previewTiles = [];
            $this->previewMeta = [];

            return;
        }

        $this->previewMeta = [
            'name' => $wb['name'] ?? '',
            'description' => $wb['description'] ?? '',
            'layout' => $wb['layout'] ?? ['columns' => 3],
        ];

        $rawTiles = $wb['tiles'] ?? [];
        if (empty($rawTiles)) {
            $this->previewTiles = [];

            return;
        }

        try {
            $resolved = $this->service()->resolveWallboardTiles($rawTiles, true);
            $this->previewTiles = $resolved['tiles'] ?? [];
        } catch (Throwable) {
            $this->previewTiles = [];
        }
    }

    public function selectWallboard(string $uuid): void
    {
        $this->selectedWallboardUuid = $uuid;
        $this->refreshWallboardPreview();
    }

    public function openCreateWallboardForm(): void
    {
        $this->showWallboardForm = true;
        $this->editingWallboardUuid = null;
        $this->editingIsDefault = false;
        $this->wallboardForm = ['name' => '', 'description' => '', 'columns' => 3, 'refresh_seconds' => 10, 'tiles' => []];
        $this->newTileMetric = null;
        $this->newTileQueue = null;
    }

    public function openEditWallboard(string $uuid): void
    {
        $wb = collect($this->wallboards)->firstWhere('uuid', $uuid);
        if ($wb === null) {
            return;
        }

        $this->showWallboardForm = true;
        $this->editingWallboardUuid = $uuid;
        $this->editingIsDefault = (bool) ($wb['is_default'] ?? false);
        $this->wallboardForm = [
            'name' => $wb['name'] ?? '',
            'description' => $wb['description'] ?? '',
            'columns' => (int) ($wb['layout']['columns'] ?? 3),
            'refresh_seconds' => (int) ($wb['layout']['refresh_seconds'] ?? 10),
            'tiles' => array_map(fn (array $t): array => [
                'metric' => $t['metric'] ?? null,
                'queue' => $t['queue'] ?? null,
                'title' => $t['title'] ?? null,
            ], $wb['tiles'] ?? []),
        ];
        $this->newTileMetric = null;
        $this->newTileQueue = null;
    }

    public function cancelWallboardForm(): void
    {
        $this->showWallboardForm = false;
        $this->editingWallboardUuid = null;
        $this->editingIsDefault = false;
    }

    public function addTileToForm(): void
    {
        if (empty($this->newTileMetric)) {
            return;
        }

        $this->wallboardForm['tiles'][] = [
            'metric' => $this->newTileMetric,
            'queue' => $this->newTileQueue !== null && $this->newTileQueue !== '' ? (int) $this->newTileQueue : null,
            'title' => null,
        ];

        $this->newTileMetric = null;
        $this->newTileQueue = null;
    }

    public function removeTileFromForm(int $index): void
    {
        if (isset($this->wallboardForm['tiles'][$index])) {
            array_splice($this->wallboardForm['tiles'], $index, 1);
        }
    }

    public function saveWallboard(): void
    {
        $this->ensureCanModify();

        if ($this->activeHostName() === null) {
            $this->flashWarning('config.noHost');

            return;
        }

        if (trim((string) $this->wallboardForm['name']) === '' || empty($this->wallboardForm['tiles'])) {
            $this->flashWarning('config.wallboard.nameAndTilesRequired');

            return;
        }

        $payload = [
            'name' => $this->wallboardForm['name'],
            'description' => $this->wallboardForm['description'] ?: null,
            'tiles' => array_values($this->wallboardForm['tiles']),
            'layout' => [
                'columns' => (int) $this->wallboardForm['columns'],
                'refresh_seconds' => (int) $this->wallboardForm['refresh_seconds'],
            ],
        ];

        try {
            if ($this->editingWallboardUuid !== null) {
                $this->service()->updateWallboard($this->editingWallboardUuid, $payload);
                $selected = $this->editingWallboardUuid;
            } else {
                $created = $this->service()->createWallboard($payload);
                $selected = $created['uuid'] ?? null;
            }

            $this->showWallboardForm = false;
            $this->editingWallboardUuid = null;
            $this->loadWallboards();
            if ($selected !== null) {
                $this->selectWallboard($selected);
            }
            $this->flashSuccess('config.saved');
        } catch (Throwable) {
            $this->flashError('config.wallboard.limitOrError');
        }
    }

    public function deleteWallboard(string $uuid): void
    {
        $this->ensureCanModify();

        try {
            $this->service()->deleteWallboard($uuid);
            if ($this->selectedWallboardUuid === $uuid) {
                $this->selectedWallboardUuid = null;
            }
            $this->loadWallboards();
            $this->flashSuccess('config.deleted');
        } catch (Throwable) {
            $this->flashError('config.wallboard.cannotDeleteDefault');
        }
    }

    public function revertWallboard(string $uuid): void
    {
        $this->ensureCanModify();

        try {
            $this->service()->revertWallboard($uuid);
            $this->loadWallboards();
            $this->selectWallboard($uuid);
            $this->flashSuccess('config.wallboard.reverted');
        } catch (Throwable) {
            $this->flashError('config.saveFailed');
        }
    }

    public function toggleWallboardActive(string $uuid): void
    {
        $this->ensureCanModify();

        $wb = collect($this->wallboards)->firstWhere('uuid', $uuid);
        $active = ! (bool) ($wb['active'] ?? true);

        try {
            // ExpertStatisticsService::activateWallboard() sends no request
            // body even though the backend's setActive() action requires
            // {active: bool} — a defect in the SDK's SetActiveWallboardRequest
            // (out of scope to fix here, see cx-engine/expert-stats-api-sdk-php).
            // updateWallboard() hits a request class that does forward an
            // `active` field and achieves the same effect.
            $this->service()->updateWallboard($uuid, ['active' => $active]);
            $this->loadWallboards();
        } catch (Throwable) {
            $this->flashError('config.saveFailed');
        }
    }

    public function buildWallboardWithAi(): void
    {
        $this->ensureCanModify();

        if ($this->activeHostName() === null || trim($this->wallboardPrompt) === '') {
            return;
        }

        $this->wallboardBuilding = true;

        try {
            $resp = $this->service()->buildWallboardWithAi([
                'prompt' => $this->wallboardPrompt,
                'language' => app()->getLocale() === 'en' ? 'en' : 'fr',
            ]);
            $this->wallboardAiResult = $resp;
        } catch (Throwable) {
            $this->wallboardAiResult = null;
            $this->flashError('config.wallboard.aiFailed');
        } finally {
            $this->wallboardBuilding = false;
        }
    }

    public function saveAiWallboard(): void
    {
        $this->ensureCanModify();

        if ($this->activeHostName() === null || $this->wallboardAiResult === null) {
            return;
        }

        $tiles = array_map(fn (array $t): array => [
            'metric' => $t['metric'] ?? null,
            'queue' => $t['queue'] ?? null,
            'title' => $t['title'] ?? null,
        ], $this->wallboardAiResult['tiles'] ?? []);

        $payload = [
            'name' => $this->wallboardAiResult['name'] ?: __('expert-statistics::pbx.config.wallboard.aiDefaultName'),
            'description' => $this->wallboardAiResult['description'] ?? null,
            'tiles' => $tiles,
            'layout' => $this->wallboardAiResult['layout'] ?? ['columns' => 3, 'refresh_seconds' => 10],
            'source' => 'ai',
        ];

        try {
            $created = $this->service()->createWallboard($payload);
            $this->wallboardAiResult = null;
            $this->wallboardPrompt = '';
            $this->loadWallboards();
            if (isset($created['uuid'])) {
                $this->selectWallboard($created['uuid']);
            }
            $this->flashSuccess('config.saved');
        } catch (Throwable) {
            $this->flashError('config.wallboard.limitOrError');
        }
    }

    public function discardAiWallboard(): void
    {
        $this->wallboardAiResult = null;
    }

    /**
     * Points at this app's own public wallboard route (added in a later
     * phase — modules/ExpertStatistics/Routes/tenant.php), not
     * bluerocktelclients' url("/wallboard/{key}"). Falls back to '#' while
     * that route doesn't exist yet, so the wallboard list doesn't blow up
     * with a RouteNotFoundException in the meantime.
     */
    public function shareUrl(string $key): string
    {
        if (! Route::has('expert-stats.wallboard.public')) {
            return '#';
        }

        return route('expert-stats.wallboard.public', ['key' => $key]);
    }

    // ── Utilities ─────────────────────────────────────────────────────────

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeBooleans(array $data): array
    {
        $boolFields = [
            'answered_percentage_red_active',
            'answered_percentage_orange_active',
            'answered_percentage_yellow_active',
            'answered_percentage_green_active',
            'duration_avg_answer_red_active',
            'duration_avg_answer_orange_active',
            'duration_avg_answer_yellow_active',
            'duration_avg_answer_green_active',
            'duration_avg_answer_user_red_active',
            'duration_avg_answer_user_orange_active',
            'duration_avg_answer_user_yellow_active',
            'duration_avg_answer_user_green_active',
            'duration_avg_call_red_active',
            'duration_avg_call_orange_active',
            'duration_avg_call_yellow_active',
            'duration_avg_call_green_active',
            'ratio_solicitations_red_active',
            'ratio_solicitations_orange_active',
            'ratio_solicitations_yellow_active',
            'ratio_solicitations_green_active',
        ];

        foreach ($boolFields as $field) {
            if (array_key_exists($field, $data)) {
                $data[$field] = (bool) $data[$field];
            }
        }

        return $data;
    }
}
