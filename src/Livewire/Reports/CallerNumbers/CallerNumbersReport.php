<?php

namespace CXEngine\ExpertStatistics\Livewire\Reports\CallerNumbers;

use Carbon\Carbon;
use CXEngine\ExpertStatistics\Concerns\AuthorizesExpertStatisticsAccess;
use CXEngine\ExpertStatistics\Concerns\HasPbxElementSelector;
use CXEngine\ExpertStatistics\Concerns\RequiresExpertStatisticsActivation;
use CXEngine\ExpertStatistics\Services\ExpertStatisticsService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

class CallerNumbersReport extends Component
{
    use AuthorizesExpertStatisticsAccess;
    use RequiresExpertStatisticsActivation;
    use HasPbxElementSelector {
        addElement as protected baseAddElement;
        removePbxGroup as protected baseRemovePbxGroup;
    }

    /**
     * Sentinel stored in $groupSelectedName when every group is selected. Keeps the
     * URL and the API query string short for customers with hundreds of groups.
     */
    public const string ALL_GROUPS = '*';

    public string $urlType = 'caller';

    #[Url]
    public string $selectedPeriod = 'lastMonth';

    #[Url]
    public ?string $startDate = null;

    #[Url]
    public ?string $endDate = null;

    #[Url]
    public string $startTime = '07:00';

    #[Url]
    public string $endTime = '19:00';

    public string $dn = '';

    /** @var string[] Names of currently selected resource groups (URL-persisted; the API receives group names, never raw numbers) */
    #[Url(as: 'groups')]
    public array $groupSelectedName = [];

    // Active tab: 'groups' | 'groupless'
    #[Url]
    public string $activeTab = 'groups';

    // View mode within each tab: 'group' (caller/group rows) | 'queue' (by destination queue)
    #[Url]
    public string $viewBy = 'group';

    public bool $isLoading = false;

    /** @var array<int, array<string, mixed>> Group-view rows */
    public array $tableData = [];

    /** @var array<int, array<string, mixed>> Queue-view rows */
    public array $queueTableData = [];

    /** @var array<int, array<string, mixed>> Groupless individual caller rows */
    public array $grouplessTableData = [];

    /** @var array<string, mixed> */
    public array $consolidated = [];

    public bool $showConsolidated = false;

    public bool $excludeClosedHours = false;

    /** @var string[] Available queue DNs returned by the API */
    public array $availableQueues = [];

    /** @var array<string, string> Maps queue DN → "DN — Queue Name" for display */
    public array $queueNameMap = [];

    /** @var string[] Currently-selected queue DNs for filtering */
    public array $selectedQueues = [];

    // Pagination
    public int $currentPage = 1;

    public int $perPage = 50;

    public int $totalItems = 0;

    public int $lastPage = 1;

    public string $sortField = '';

    public string $sortDirection = 'asc';

    public ?string $errorMessage = null;

    public function mount(): void
    {
        $this->restoreExpertStatsFilters();
        $this->applyPeriod($this->selectedPeriod);
        $this->loadPbxElements();
        $this->loadQueueNames();
        $this->restoreGroupSelection();
        $this->loadData();
    }

    public function selectPeriod(string $value): void
    {
        $this->selectedPeriod = $value;
        $this->showDatePicker = false;
        $this->applyPeriod($value);
        $this->saveExpertStatsPeriod();
        $this->resetPage();
        $this->loadData();
    }

    public function applyFilters(): void
    {
        $this->resetPage();
        $this->loadData();
    }

    public function toggleConsolidated(): void
    {
        $this->showConsolidated = ! $this->showConsolidated;
    }

    /** Called by Livewire after wire:model updates excludeClosedHours. */
    public function updatedExcludeClosedHours(): void
    {
        $this->resetPage();
        $this->loadData();
    }

    /**
     * @param  array{label: string, value: string|string[], isConstructor?: bool, group?: bool}  $element
     */
    public function addElement(array $element): void
    {
        if (is_array($element['value'] ?? null) && $this->isAllGroupsSelected()) {
            // Expand the sentinel into real names so the base toggle can deselect the clicked group
            $this->groupSelectedName = $this->allGroupNames();
        }

        $this->baseAddElement($element);
    }

    public function removePbxGroup(string $groupName): void
    {
        if ($groupName === self::ALL_GROUPS) {
            $this->clearPbxElements();

            return;
        }

        $this->baseRemovePbxGroup($groupName);
    }

    /**
     * Selecting all groups stores only the sentinel — never the (possibly 500+) group
     * names, nor their member numbers, in the component state or the URL.
     */
    public function selectAllPbxElements(): void
    {
        $this->groupSelectedName = [self::ALL_GROUPS];
        $this->groupSelected = true;
        $this->selectedElements = [];
        $this->elementGroupMap = [];
        $this->dn = '';
        $this->resetPage();
        $this->loadData();
    }

    public function setTab(string $tab): void
    {
        if (! in_array($tab, ['groups', 'groupless'], true)) {
            return;
        }
        $this->activeTab = $tab;
        $this->viewBy = 'group';
        $this->selectedQueues = [];
        $this->showConsolidated = false;
        $this->resetPage();
        $this->loadData();
    }

    public function setViewBy(string $mode): void
    {
        if (! in_array($mode, ['group', 'queue'], true)) {
            return;
        }
        $this->viewBy = $mode;
        $this->resetPage();
        $this->loadData();
    }

    public function toggleQueueFilter(string $queueDn): void
    {
        $idx = array_search($queueDn, $this->selectedQueues, true);
        if ($idx !== false) {
            array_splice($this->selectedQueues, (int) $idx, 1);
        } else {
            $this->selectedQueues[] = $queueDn;
        }
        $this->resetPage();
        $this->loadData();
    }

    public function clearQueueFilter(): void
    {
        $this->selectedQueues = [];
        $this->resetPage();
        $this->loadData();
    }

    public function goToPage(int $page): void
    {
        $this->currentPage = max(1, min($page, $this->lastPage));
        $this->loadData();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
        $this->loadData();
    }

    /** Opens CXEngine\ExpertStatistics\Livewire\Reports\ShareReportModal in "send" mode. */
    public function shareReport(): void
    {
        $this->dispatch('open-share-report',
            urlType: $this->urlType,
            startDate: $this->startDate,
            endDate: $this->endDate,
            startTime: $this->startTime,
            endTime: $this->endTime,
            callerFilters: $this->activeTab === 'groupless'
                ? ['groupless' => true]
                : ($this->isAllGroupsSelected()
                    ? ['all_groups' => true, 'queues' => $this->selectedQueues]
                    : ['group_names' => $this->groupSelectedName, 'queues' => $this->selectedQueues]),
        );
    }

    /** Opens CXEngine\ExpertStatistics\Livewire\Reports\ShareReportModal in "schedule" mode. */
    public function scheduleReport(): void
    {
        $this->dispatch('open-schedule-report',
            urlType: $this->urlType,
            startDate: $this->startDate,
            endDate: $this->endDate,
            startTime: $this->startTime,
            endTime: $this->endTime,
            callerFilters: $this->activeTab === 'groupless'
                ? ['groupless' => true]
                : ($this->isAllGroupsSelected()
                    ? ['all_groups' => true, 'queues' => $this->selectedQueues]
                    : ['group_names' => $this->groupSelectedName, 'queues' => $this->selectedQueues]),
        );
    }

    public function loadData(): void
    {
        $this->normalizeGroupSelection();

        if (! $this->startDate || ! $this->endDate) {
            return;
        }

        // Groups tab requires at least a group selection
        if ($this->activeTab === 'groups' && empty($this->groupSelectedName)) {
            return;
        }

        $this->isLoading = true;
        $this->errorMessage = null;
        $this->tableData = [];
        $this->queueTableData = [];
        $this->grouplessTableData = [];
        $this->consolidated = [];

        try {
            $params = [
                'start_date' => $this->startDate,
                'end_date' => $this->endDate,
                'start_time' => $this->startTime,
                'end_time' => $this->endTime,
                'page' => $this->currentPage,
                'per_page' => $this->perPage,
                'view_by' => $this->viewBy,
                'exclude_closed_hours' => $this->excludeClosedHours ? 1 : 0,
            ];

            if ($this->sortField !== '') {
                $params['sort_field'] = $this->sortField;
                $params['sort_direction'] = $this->sortDirection;
            }

            if ($this->activeTab === 'groupless') {
                $params['groupless'] = 1;
            } elseif ($this->isAllGroupsSelected()) {
                // Let the API resolve "all groups" itself — enumerating 500+ names breaks query-string limits
                $params['all_groups'] = 1;
            } else {
                // The API resolves group names to numbers itself; never send the raw number list (can be 800+ items)
                $params['group_names'] = implode(',', $this->groupSelectedName);
            }

            if (! empty($this->selectedQueues)) {
                $params['queues'] = implode(',', $this->selectedQueues);
            }

            $raw = app(ExpertStatisticsService::class)->getCallersReport($params);

            // Pagination metadata
            $meta = $raw['meta'] ?? [];
            $this->totalItems = (int) ($meta['total'] ?? 0);
            $this->lastPage = (int) ($meta['last_page'] ?? 1);
            $this->currentPage = (int) ($meta['current_page'] ?? 1);

            // Available queues for filter pills
            $this->availableQueues = $raw['available_queues'] ?? [];

            if ($this->viewBy === 'queue') {
                $this->queueTableData = array_values($raw['report'] ?? []);
            } elseif ($this->activeTab === 'groupless') {
                $this->grouplessTableData = array_values($raw['report'] ?? []);
                $this->consolidated = $this->buildConsolidated($raw);
            } else {
                $this->tableData = array_values($raw['report'] ?? []);
                $this->consolidated = $this->buildConsolidated($raw);
            }
        } catch (\Throwable $e) {
            $this->errorMessage = 'Erreur lors du chargement des données.';
        }

        $this->isLoading = false;
    }

    /** @return array<int, array<string, mixed>> */
    public function getDisplayData(): array
    {
        if ($this->viewBy === 'queue') {
            return array_values($this->queueTableData);
        }

        if ($this->showConsolidated) {
            return empty($this->consolidated) ? [] : [$this->consolidated];
        }

        return array_values($this->activeTab === 'groupless' ? $this->grouplessTableData : $this->tableData);
    }

    public function formatDuration(mixed $seconds): string
    {
        $secs = (int) $seconds;
        if ($secs <= 0) {
            return '0s';
        }
        $hours = intdiv($secs, 3600);
        $mins = intdiv($secs % 3600, 60);
        $remaining = $secs % 60;

        if ($hours > 0) {
            return "{$hours}h {$mins}m";
        }

        return $mins > 0 ? "{$mins}m {$remaining}s" : "{$remaining}s";
    }

    /** Whether the current tab/view has any loaded data */
    public function hasData(): bool
    {
        if ($this->viewBy === 'queue') {
            return ! empty($this->queueTableData);
        }

        return $this->activeTab === 'groupless'
            ? ! empty($this->grouplessTableData)
            : ! empty($this->tableData);
    }

    public function render(): View
    {
        return view('expert-statistics::livewire.reports.caller-numbers.report');
    }

    private function isAllGroupsSelected(): bool
    {
        return in_array(self::ALL_GROUPS, $this->groupSelectedName, true);
    }

    /** @return string[] */
    private function allGroupNames(): array
    {
        return array_values(array_map(
            fn (array $element): string => (string) $element['label'],
            array_filter($this->pbxElements, fn (array $element): bool => $element['isConstructor'] ?? false),
        ));
    }

    /**
     * Collapses a manually-built full selection into the ALL_GROUPS sentinel so the
     * URL and API requests stay short, and drops the per-number state that is only
     * needed to compute the (now unused) raw DN list.
     */
    private function normalizeGroupSelection(): void
    {
        if ($this->isAllGroupsSelected()) {
            return;
        }

        $allNames = $this->allGroupNames();

        if ($allNames === [] || empty($this->groupSelectedName) || array_diff($allNames, $this->groupSelectedName) !== []) {
            return;
        }

        $this->groupSelectedName = [self::ALL_GROUPS];
        $this->selectedElements = [];
        $this->elementGroupMap = [];
        $this->dn = '';
    }

    /**
     * Rebuilds the selector state (chips, group map, internal DN list) from the
     * URL-persisted group names after a page load.
     */
    private function restoreGroupSelection(): void
    {
        if (empty($this->groupSelectedName)) {
            return;
        }

        if ($this->isAllGroupsSelected()) {
            $this->groupSelectedName = [self::ALL_GROUPS];
            $this->groupSelected = true;

            return;
        }

        foreach ($this->pbxElements as $element) {
            if (! ($element['isConstructor'] ?? false) || ! in_array($element['label'], $this->groupSelectedName, true)) {
                continue;
            }

            foreach ((array) $element['value'] as $rawValue) {
                $value = $this->normalizeValue((string) $rawValue);

                if (! isset($this->elementGroupMap[$value])) {
                    $this->elementGroupMap[$value] = [];
                }

                if (! in_array($element['label'], $this->elementGroupMap[$value], true)) {
                    $this->elementGroupMap[$value][] = $element['label'];
                }

                if (! in_array($value, $this->selectedElements, true)) {
                    $this->selectedElements[] = $value;
                }
            }
        }

        $this->groupSelected = true;
        $this->dn = implode(',', $this->selectedElements);
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>
     */
    private function buildConsolidated(array $raw): array
    {
        $aggregated = $raw['aggregated'] ?? [];

        $total = (int) ($aggregated['inbound_total'] ?? 0);
        $answered = (int) ($aggregated['inbound_answered'] ?? 0);
        $talkingTotal = (int) ($aggregated['duration_total'] ?? 0);

        // Fall back to summing displayed rows when API totals are absent
        if ($total === 0) {
            $rows = $raw['report'] ?? [];
            $total = (int) array_sum(array_column($rows, 'inbound_total'));
            $answered = (int) array_sum(array_column($rows, 'inbound_answered'));
            $talkingTotal = (int) array_sum(array_column($rows, 'talking_duration_total'));
        }

        $waitingTotal = (int) ($aggregated['waiting_total'] ?? 0);
        if ($waitingTotal === 0) {
            $waitingTotal = (int) array_sum(array_column($raw['report'] ?? [], 'waiting_duration_total'));
        }

        $unanswered = $total - $answered;

        return [
            'caller_number' => 'TOTAL',
            'inbound_total' => $total,
            'inbound_answered' => $answered,
            'inbound_unanswered' => $unanswered,
            'response_rate' => $total > 0 ? round($answered / $total * 100, 1) : 0,
            'talking_duration_total' => $talkingTotal,
            'talking_duration_avg' => $answered > 0 ? (int) round($talkingTotal / $answered) : 0,
            'waiting_duration_total' => $waitingTotal,
            'waiting_duration_avg' => $answered > 0 ? (int) round($waitingTotal / $answered) : 0,
        ];
    }

    private function loadQueueNames(): void
    {
        try {
            $map = app(ExpertStatisticsService::class)->getMap();
            $this->queueNameMap = collect($map['call_queues'] ?? [])
                ->map(fn (mixed $q, string $dn): string => $dn.' — '.(is_array($q) ? ($q['name'] ?? $dn) : $dn))
                ->all();
        } catch (\Throwable) {
            $this->queueNameMap = [];
        }
    }

    private function resetPage(): void
    {
        $this->currentPage = 1;
    }

    private function applyPeriod(string $period): void
    {
        $today = Carbon::today();

        match ($period) {
            'today' => $this->setRange($today->copy(), $today->copy()),
            'yesterday' => $this->setRange($today->copy()->subDay(), $today->copy()->subDay()),
            'currentWeek' => $this->setRange($today->copy()->startOfWeek(), $today->copy()),
            'lastWeek' => $this->setRange($today->copy()->subWeek()->startOfWeek(), $today->copy()->subWeek()->endOfWeek()),
            'currentMonth' => $this->setRange($today->copy()->startOfMonth(), $today->copy()),
            'lastMonth' => $this->setRange($today->copy()->subMonth()->startOfMonth(), $today->copy()->subMonth()->endOfMonth()),
            default => null,
        };
    }

    private function setRange(Carbon $start, Carbon $end): void
    {
        $this->startDate = $start->format('Y-m-d');
        $this->endDate = $end->format('Y-m-d');
    }
}
