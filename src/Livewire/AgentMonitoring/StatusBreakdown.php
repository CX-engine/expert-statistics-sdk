<?php

namespace CXEngine\ExpertStatistics\Livewire\AgentMonitoring;

use Carbon\Carbon;
use CXEngine\ExpertStatistics\Concerns\AuthorizesExpertStatisticsAccess;
use CXEngine\ExpertStatistics\Concerns\HasPbxElementSelector;
use CXEngine\ExpertStatistics\Concerns\RequiresExpertStatisticsActivation;
use CXEngine\ExpertStatistics\Services\ExpertStatisticsService;
use CXEngine\ExpertStatistics\Support\PbxDataProcessor;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Per-agent status-time breakdown report, ported from bluerocktelclients'
 * App\Filament\Pages\AgentMonitoring\StatusBreakdownPage. Plain Livewire
 * full-page component: no Filament page, no report scheduling/sharing/
 * export UI (out of scope for this package). Like QueueConnection, this
 * page has no polling in the source - it is a period/agent-filtered report
 * like the other HasPbxElementSelector pages.
 */
class StatusBreakdown extends Component
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

    public bool $isLoading = false;

    public bool $showConsolidated = false;

    /** @var array<int, array<string, mixed>> */
    public array $breakdown = [];

    /** @var array<int, array<string, mixed>> */
    public array $dailyActivity = [];

    /** @var array<string, string> */
    public array $statusMapping = [];

    /** @var array<string, mixed> */
    public array $summary = [];

    /** @var array<string, string> */
    public array $extensionsMap = [];

    public ?string $errorMessage = null;

    public function mount(): void
    {
        $this->restoreExpertStatsFilters();
        $this->applyPeriod($this->selectedPeriod);
        $this->loadPbxElements();
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

    public function loadData(): void
    {
        if (! $this->dn || ! $this->startDate || ! $this->endDate) {
            return;
        }

        $this->isLoading = true;
        $this->errorMessage = null;
        $this->breakdown = [];
        $this->dailyActivity = [];
        $this->statusMapping = [];
        $this->summary = [];

        try {
            $service = app(ExpertStatisticsService::class);

            $raw = $service->getAgentStatsStatusBreakdown([
                'start_date' => $this->startDate,
                'end_date' => $this->endDate,
                'start_time' => $this->startTime,
                'end_time' => $this->endTime,
                'user_dns' => $this->dn,
            ]);
            $this->breakdown = $raw['data'] ?? [];
            $this->statusMapping = $raw['status_mapping'] ?? [];
            $this->summary = $raw['summary'] ?? [];

            $daily = $service->getAgentStatsDailyActivity([
                'start_date' => $this->startDate,
                'end_date' => $this->endDate,
                'user_dns' => $this->dn,
            ]);
            $this->dailyActivity = $daily['data'] ?? [];
        } catch (\Throwable) {
            $this->errorMessage = __('expert-statistics::pbx.expert_statistics.error_loading');
        }

        $this->loadExtensionsMap();

        $this->isLoading = false;
    }

    public function getAgentLabel(string $dn): string
    {
        $name = $this->extensionsMap[$dn] ?? null;

        return $name !== null ? "{$dn} — {$name}" : $dn;
    }

    /**
     * Group the raw (user_dn, status) rows by agent.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getPerAgentBreakdown(): array
    {
        return array_map(fn (array $agent): array => [
            'user_dn' => $agent['user_dn'],
            'statuses' => array_map(fn (array $item): array => [
                'status' => (int) $item['key'],
                'status_name' => $this->statusLabel($item['key']),
                'total_minutes' => $item['minutes'],
            ], $agent['items']),
            'total_minutes' => $agent['total_minutes'],
        ], $this->groupedByAgent());
    }

    /**
     * Sum total_minutes across all agents, grouped by status.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAggregateByStatus(): array
    {
        $items = PbxDataProcessor::agentMonitoringAggregateByKey($this->breakdown, 'status', $this->statusMinutesResolver());

        return array_map(fn (array $item): array => [
            'status' => (int) $item['key'],
            'status_name' => $this->statusLabel($item['key']),
            'minutes' => $item['minutes'],
        ], $items);
    }

    /**
     * Aggregate items for the donut chart: {label, minutes, color} per status.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAggregateItems(): array
    {
        return array_map(fn (array $status): array => [
            'label' => $status['status_name'],
            'minutes' => $status['minutes'],
            'color' => $this->getStatusColor($status['status']),
        ], $this->getAggregateByStatus());
    }

    /**
     * Categories + series (ApexCharts-ready) for the per-agent stacked bar chart.
     *
     * @return array{categories: array<int, string>, series: array<int, array<string, mixed>>}
     */
    public function getPerAgentSeries(): array
    {
        $buckets = array_map(
            fn (array $agent): array => ['label' => $this->getAgentLabel($agent['user_dn']), 'items' => $agent['items']],
            $this->groupedByAgent()
        );

        return PbxDataProcessor::agentMonitoringBuildStackedSeries($buckets, $this->statusNameResolver(), $this->statusColorResolver());
    }

    /**
     * Categories + series (ApexCharts-ready) for the daily stacked bar chart:
     * total time in each status, per day, summed across the selected agents.
     *
     * Used for the consolidated view only — see getDailySeriesPerAgent() for the
     * per-agent breakdown shown in the detailed view.
     *
     * @return array{categories: array<int, string>, series: array<int, array<string, mixed>>}
     */
    public function getDailySeries(): array
    {
        $buckets = PbxDataProcessor::agentMonitoringGroupByDate($this->flattenedDailyActivity(), 'date', 'status', $this->flatMinutesResolver());

        return PbxDataProcessor::agentMonitoringBuildStackedSeries($buckets, $this->statusNameResolver(), $this->statusColorResolver());
    }

    /**
     * One daily stacked-bar series per agent: time in each status, per day,
     * for that agent alone.
     *
     * @return array<int, array{user_dn: string, categories: array<int, string>, series: array<int, array<string, mixed>>}>
     */
    public function getDailySeriesPerAgent(): array
    {
        $flat = $this->flattenedDailyActivity();
        $result = [];

        foreach ($this->dailyActivityAgentDns() as $dn) {
            $rows = array_values(array_filter($flat, fn (array $row): bool => $row['user_dn'] === $dn));
            $buckets = PbxDataProcessor::agentMonitoringGroupByDate($rows, 'date', 'status', $this->flatMinutesResolver());

            $result[] = ['user_dn' => $dn, ...PbxDataProcessor::agentMonitoringBuildStackedSeries($buckets, $this->statusNameResolver(), $this->statusColorResolver())];
        }

        return $result;
    }

    public function getStatusColor(int $status): string
    {
        return match ($status) {
            0 => '#22c55e',
            4 => '#eab308',
            3, 9 => '#ef4444',
            1, 2 => '#6366f1',
            default => '#9ca3af',
        };
    }

    public function formatMinutes(int $minutes): string
    {
        if ($minutes <= 0) {
            return '0m';
        }

        $h = intdiv($minutes, 60);
        $m = $minutes % 60;

        return ($h > 0 ? "{$h}h " : '')."{$m}m";
    }

    public function render(): View
    {
        return view('expert-statistics::livewire.agent-monitoring.status-breakdown');
    }

    /** @return array<int, array{user_dn: string, items: array<int, array{key: string, minutes: int}>, total_minutes: int}> */
    private function groupedByAgent(): array
    {
        return PbxDataProcessor::agentMonitoringGroupByAgent($this->breakdown, 'user_dn', 'status', $this->statusMinutesResolver());
    }

    /** @return array<int, array{date: string, user_dn: string, status: string, total_minutes: int}> */
    private function flattenedDailyActivity(): array
    {
        return PbxDataProcessor::flattenAgentMonitoringDailyActivity($this->dailyActivity);
    }

    /** Distinct agent DNs from $dailyActivity, in API response order (not re-sorted). */
    private function dailyActivityAgentDns(): array
    {
        return array_values(array_unique(array_map(
            fn (array $agent): string => (string) ($agent['user_dn'] ?? ''),
            $this->dailyActivity,
        )));
    }

    private function statusMinutesResolver(): \Closure
    {
        return fn (array $row): int => (int) ($row['total_minutes'] ?? 0);
    }

    private function flatMinutesResolver(): \Closure
    {
        return fn (array $row): int => (int) ($row['total_minutes'] ?? 0);
    }

    private function statusNameResolver(): \Closure
    {
        return fn (string $status): string => $this->statusLabel($status);
    }

    private function statusColorResolver(): \Closure
    {
        return fn (string $status): string => $this->getStatusColor((int) $status);
    }

    /**
     * Resolve a status code (as a string key from the grouping helpers) to
     * its display name via the API's status_mapping, falling back to a
     * "#code" placeholder when unmapped.
     */
    private function statusLabel(string $status): string
    {
        return $this->statusMapping[$status] ?? $this->statusMapping[(int) $status] ?? "#{$status}";
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

    private function loadExtensionsMap(): void
    {
        try {
            $map = app(ExpertStatisticsService::class)->getMap();
            $this->extensionsMap = $map['extensions'] ?? [];
        } catch (\Throwable) {
            $this->extensionsMap = [];
        }
    }
}
