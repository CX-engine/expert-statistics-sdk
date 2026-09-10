<?php

namespace CXEngine\ExpertStatistics\Livewire\Reports\MyUsers;

use Carbon\Carbon;
use CXEngine\ExpertStatistics\Concerns\AuthorizesExpertStatisticsAccess;
use CXEngine\ExpertStatistics\Concerns\HasPbxElementSelector;
use CXEngine\ExpertStatistics\Concerns\RequiresExpertStatisticsActivation;
use CXEngine\ExpertStatistics\Services\ExpertStatisticsService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;
use Livewire\Attributes\Url;
use Livewire\Component;

class MyUsersReport extends Component
{
    use AuthorizesExpertStatisticsAccess;
    use RequiresExpertStatisticsActivation;
    use HasPbxElementSelector;

    public string $urlType = 'extension';

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

    #[Url]
    public string $dn = '';

    public bool $uniqueCalls = false;

    public bool $excludeClosedHours = false;

    public bool $showConsolidated = false;

    #[Url]
    public string $activeTab = 'calls';

    /**
     * Never populated: the relay's agent-configuration endpoint (custom status
     * labels) has no equivalent on ExpertStatisticsService. The status tab always
     * falls back to the generic "Personnalisé 1" / "Personnalisé 2" labels.
     */
    public ?string $statusCustom1 = null;

    public ?string $statusCustom2 = null;

    public bool $isLoading = false;

    public string $sortField = '';

    public string $sortDirection = 'asc';

    /** @var array<int, array<string, mixed>> */
    public array $tableData = [];

    /** @var array<string, mixed> */
    public array $consolidated = [];

    /** @var array<string, mixed> */
    public array $aggregated = [];

    public ?string $errorMessage = null;

    /** @var array<string, string> */
    public array $queueNameMap = [];

    /** @var array<string, string> */
    private array $queueColorMap = [];

    public function mount(): void
    {
        $this->restoreExpertStatsFilters();
        $this->applyPeriod($this->selectedPeriod);
        $this->loadPbxElements();
        $this->loadQueueNames();
        $this->loadData();
    }

    public function selectPeriod(string $value): void
    {
        $this->selectedPeriod = $value;
        $this->showDatePicker = false;
        $this->applyPeriod($value);
        $this->saveExpertStatsPeriod();
        $this->loadData();
    }

    public function toggleUniqueCalls(): void
    {
        $this->uniqueCalls = ! $this->uniqueCalls;
        $this->loadData();
    }

    public function updatedUniqueCalls(): void
    {
        $this->loadData();
    }

    /** Called by Livewire after wire:model updates excludeClosedHours. */
    public function updatedExcludeClosedHours(): void
    {
        $this->loadData();
    }

    public function toggleConsolidated(): void
    {
        $this->showConsolidated = ! $this->showConsolidated;
        if ($this->showConsolidated) {
            $this->activeTab = 'calls';
        }
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function switchTab(string $tab): void
    {
        if ($this->showConsolidated && in_array($tab, ['status', 'queues'], true)) {
            return;
        }
        $this->activeTab = $tab;
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
            elements: $this->dn,
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
            elements: $this->dn,
        );
    }

    /**
     * Points at UsersReportExportController's route, wired by a later phase
     * (modules/ExpertStatistics/Routes/tenant.php). Falls back to '#' while
     * that route doesn't exist yet, the same Route::has() guard used by
     * CallAnalysis::getExportUrl().
     */
    public function getExportUrl(): string
    {
        if (! $this->dn || ! $this->startDate || ! $this->endDate || ! Route::has('expert-stats.my-users.export')) {
            return '#';
        }

        $params = array_filter([
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
            'dn' => $this->dn,
            'exclude_closed_hours' => $this->excludeClosedHours ? 1 : 0,
        ]);

        return route('expert-stats.my-users.export', $params);
    }

    public function loadData(): void
    {
        if (! $this->dn || ! $this->startDate || ! $this->endDate) {
            return;
        }

        $this->isLoading = true;
        $this->errorMessage = null;
        $this->tableData = [];
        $this->consolidated = [];
        $this->aggregated = [];
        $this->queueColorMap = [];

        try {
            $params = [
                'start_date' => $this->startDate,
                'end_date' => $this->endDate,
                'start_time' => $this->startTime,
                'end_time' => $this->endTime,
                'dn' => $this->dn,
                'exclude_closed_hours' => $this->excludeClosedHours ? 1 : 0,
            ];

            $raw = app(ExpertStatisticsService::class)->getUsersReport($params);
            $this->aggregated = $raw['aggregated'] ?? [];
            $this->tableData = $raw['report'] ?? [];
            $this->consolidated = $this->buildConsolidated($raw);
        } catch (\Throwable) {
            $this->errorMessage = 'Erreur lors du chargement des données.';
        }

        $this->isLoading = false;
    }

    public function loadQueueNames(): void
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

    /** @return array<int, array<string, mixed>> */
    public function getDisplayData(): array
    {
        if ($this->showConsolidated) {
            return empty($this->consolidated) ? [] : [$this->consolidated];
        }

        $data = $this->tableData;

        if ($this->sortField !== '') {
            $data = $this->sortRows($data);
        }

        return array_values($data);
    }

    /** @return array<int, array<string, mixed>> */
    public function getStatusData(): array
    {
        if ($this->sortField === '') {
            return $this->tableData;
        }

        return $this->sortRows($this->tableData);
    }

    public function formatMinutes(mixed $minutes): string
    {
        $total = (int) $minutes;
        if ($total <= 0) {
            return '—';
        }
        $h = intdiv($total, 60);
        $m = $total % 60;

        return sprintf('%02d:%02d', $h, $m);
    }

    /** @param  array<string, mixed>  $row */
    public function availableOutOfCallFormatted(array $row): string
    {
        $availableMinutes = (int) (($row['status_summary']['available_minutes'] ?? null) ?? -1);
        $durationSeconds = (int) ($row['duration_total'] ?? -1);

        if ($availableMinutes < 0 || $durationSeconds < 0) {
            return '—';
        }

        return $this->formatMinutes(max(0, $availableMinutes - intdiv($durationSeconds, 60)));
    }

    /**
     * Per-queue chips for a status-tab row: label, formatted duration, and color,
     * sorted by duration descending. An agent can be connected to several queues at
     * the same time, so these are independent values — never sum them into a total.
     *
     * @param  array<string, mixed>  $row
     * @return array<int, array{label: string, duration: string, color: string}>
     */
    public function getQueueChips(array $row): array
    {
        return collect($row['queue_summary'] ?? [])
            ->map(fn (array $queue): array => [
                'label' => $this->getQueueLabel((string) ($queue['queue_dn'] ?? '')),
                'minutes' => (int) ($queue['total_connected_minutes'] ?? 0),
                'color' => $this->getQueueColor((string) ($queue['queue_dn'] ?? '')),
            ])
            ->sortByDesc('minutes')
            ->map(fn (array $queue): array => [
                'label' => $queue['label'],
                'duration' => $this->formatMinutes($queue['minutes']),
                'color' => $queue['color'],
            ])
            ->values()
            ->all();
    }

    /**
     * Per-agent queue connection breakdown for the dedicated "Queue connection" tab.
     *
     * An agent can be connected to several queues at the same time, so per-queue
     * durations are independent values — never summed into a per-agent total.
     *
     * @return array<int, array{user: string, queues: array<int, array{label: string, duration: string, color: string}>}>
     */
    public function getQueueConnectionRows(): array
    {
        $rows = [];

        foreach ($this->tableData as $row) {
            $queues = $this->getQueueChips($row);

            if (empty($queues)) {
                continue;
            }

            $rows[] = ['user' => (string) ($row['user'] ?? '—'), 'queues' => $queues];
        }

        return $rows;
    }

    public function getQueueLabel(string $queueDn): string
    {
        return $this->queueNameMap[$queueDn] ?? $queueDn;
    }

    public function getQueueColor(string $queueDn): string
    {
        if ($this->queueColorMap === []) {
            $this->queueColorMap = $this->buildQueueColorMap();
        }

        return $this->queueColorMap[$queueDn] ?? '#9ca3af';
    }

    /**
     * Assign a stable color to every distinct queue_dn present in the loaded report,
     * cycling through a fixed palette in sorted queue_dn order.
     *
     * @return array<string, string>
     */
    private function buildQueueColorMap(): array
    {
        $palette = [
            '#6366f1', '#22c55e', '#f59e0b', '#ef4444', '#06b6d4',
            '#a855f7', '#84cc16', '#ec4899', '#14b8a6', '#f97316',
        ];

        $queueDns = [];
        foreach ($this->tableData as $row) {
            foreach ($row['queue_summary'] ?? [] as $queue) {
                $queueDn = (string) ($queue['queue_dn'] ?? '');
                if (! in_array($queueDn, $queueDns, true)) {
                    $queueDns[] = $queueDn;
                }
            }
        }
        sort($queueDns);

        $map = [];
        foreach ($queueDns as $index => $queueDn) {
            $map[$queueDn] = $palette[$index % count($palette)];
        }

        return $map;
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

    public function render(): View
    {
        return view('expert-statistics::livewire.reports.my-users.report');
    }

    /**
     * @param  array<int, array<string, mixed>>  $data
     * @return array<int, array<string, mixed>>
     */
    private function sortRows(array $data): array
    {
        $field = $this->sortField;
        $dir = $this->sortDirection === 'asc' ? 1 : -1;

        $getValue = function (array $row) use ($field): float|string {
            return match ($field) {
                'user' => (string) ($row['user'] ?? ''),
                'status_total_connection' => (float) ($row['status_summary']['total_active_minutes'] ?? 0),
                'status_available' => (float) ($row['status_summary']['available_minutes'] ?? 0),
                'status_away' => (float) ($row['status_summary']['away_minutes'] ?? 0),
                'status_dnd' => (float) ($row['status_summary']['dnd_minutes'] ?? 0),
                'status_custom1' => (float) ($row['status_summary']['custom_1_minutes'] ?? 0),
                'status_custom2' => (float) ($row['status_summary']['custom_2_minutes'] ?? 0),
                default => (float) ($row[$field] ?? 0),
            };
        };

        usort($data, fn ($a, $b): int => ($getValue($a) <=> $getValue($b)) * $dir);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>
     */
    private function buildConsolidated(array $raw): array
    {
        $rows = $raw['report'] ?? [];
        $aggregated = $raw['aggregated'] ?? [];
        $count = count($rows);

        $c = [
            'user' => '—',
            'outbound_answered' => 0,
            'outbound_talking_duration_total' => 0,
            'outbound_talking_duration_avg' => 0,
            'inbound_answered' => 0,
            'inbound_unanswered' => 0,
            'inbound_answered_percentage' => 0,
            'inbound_talking_duration_total' => 0,
            'inbound_talking_duration_avg' => 0,
            'inbound_waiting_duration_avg' => 0,
            'internal_answered' => 0,
            'internal_made_answered' => 0,
            'internal_received_answered' => 0,
            'internal_talking_duration_total' => 0,
            'internal_talking_duration_avg' => 0,
            'answered_calls' => 0,
            'duration_total' => 0,
            'duration_avg' => 0,
        ];

        foreach ($rows as $row) {
            $c['outbound_answered'] += (int) ($row['outbound_answered'] ?? 0);
            $c['outbound_talking_duration_total'] += (int) ($row['outbound_talking_duration_total'] ?? 0);
            $c['outbound_talking_duration_avg'] += (float) ($row['outbound_talking_duration_avg'] ?? 0);
            $c['inbound_answered'] += (int) ($row['inbound_answered'] ?? 0);
            $c['inbound_unanswered'] += (int) ($row['inbound_unanswered'] ?? 0);
            $c['inbound_talking_duration_total'] += (int) ($row['inbound_talking_duration_total'] ?? 0);
            $c['inbound_talking_duration_avg'] += (float) ($row['inbound_talking_duration_avg'] ?? 0);
            $c['inbound_waiting_duration_avg'] += (float) ($row['inbound_waiting_duration_avg'] ?? 0);
            $c['internal_answered'] += (int) ($row['internal_answered'] ?? 0);
            $c['internal_made_answered'] += (int) ($row['internal_made_answered'] ?? 0);
            $c['internal_received_answered'] += (int) ($row['internal_received_answered'] ?? 0);
            $c['internal_talking_duration_total'] += (int) ($row['internal_talking_duration_total'] ?? 0);
            $c['internal_talking_duration_avg'] += (float) ($row['internal_talking_duration_avg'] ?? 0);
            $c['answered_calls'] += (int) ($row['answered_calls'] ?? 0);
            $c['duration_total'] += (int) ($row['duration_total'] ?? 0);
            $c['duration_avg'] += (float) ($row['duration_avg'] ?? 0);
        }

        if ($this->uniqueCalls && $aggregated) {
            $c['inbound_answered'] = (int) ($aggregated['inbound_answered_calls'] ?? $c['inbound_answered']);
            $inboundTotal = (int) ($aggregated['inbound_calls'] ?? 0);
            $c['inbound_unanswered'] = max(0, $inboundTotal - $c['inbound_answered']);
        }

        $inboundTotal2 = $c['inbound_answered'] + $c['inbound_unanswered'];
        $c['inbound_answered_percentage'] = $inboundTotal2 > 0
            ? (int) round($c['inbound_answered'] / $inboundTotal2 * 100)
            : 0;

        if ($count > 0) {
            $c['outbound_talking_duration_avg'] = round($c['outbound_talking_duration_avg'] / $count);
            $c['inbound_talking_duration_avg'] = round($c['inbound_talking_duration_avg'] / $count);
            $c['inbound_waiting_duration_avg'] = round($c['inbound_waiting_duration_avg'] / $count);
            $c['internal_talking_duration_avg'] = round($c['internal_talking_duration_avg'] / $count);
            $c['duration_avg'] = round($c['duration_avg'] / $count);
        }

        return $c;
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
