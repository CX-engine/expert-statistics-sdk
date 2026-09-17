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
 * Per-agent queue-connection report, ported from bluerocktelclients'
 * App\Filament\Pages\AgentMonitoring\QueueConnectionPage. Plain Livewire
 * full-page component: no Filament page, no report scheduling/sharing/
 * export UI (out of scope for this package). Unlike RealtimeStatus, this
 * page has no polling in the source - it is a period/agent-filtered report
 * like the other HasPbxElementSelector pages.
 */
class QueueConnection extends Component
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

    /** @var array<string, mixed> */
    public array $summary = [];

    public ?string $errorMessage = null;

    /** @var array<string, string> */
    public array $extensionsMap = [];

    /** @var array<string, string> */
    public array $queueNameMap = [];

    /** @var array<string, string> */
    private array $queueColorMap = [];

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
        $this->summary = [];
        $this->queueColorMap = [];

        try {
            $raw = app(ExpertStatisticsService::class)->getAgentStatsQueueConnection([
                'start_date' => $this->startDate,
                'end_date' => $this->endDate,
                'user_dns' => $this->dn,
            ]);
            $this->breakdown = $raw['data'] ?? [];
            $this->summary = $raw['summary'] ?? [];
        } catch (\Throwable) {
            $this->errorMessage = __('expert-statistics::pbx.expert_statistics.error_loading');
        }

        $this->loadNameMaps();

        $this->isLoading = false;
    }

    public function getAgentLabel(string $dn): string
    {
        $name = $this->extensionsMap[$dn] ?? null;

        return $name !== null ? "{$dn} — {$name}" : $dn;
    }

    public function getQueueLabel(string $queueDn): string
    {
        return $this->queueNameMap[$queueDn] ?? $queueDn;
    }

    /**
     * Sum connected minutes across all agents and days, grouped by queue.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAggregateByQueue(): array
    {
        $items = PbxDataProcessor::agentMonitoringAggregateByKey($this->breakdown, 'queue_dn', $this->connectedMinutesResolver());

        return array_map(fn (array $item): array => [
            'label' => $this->getQueueLabel($item['key']),
            'minutes' => $item['minutes'],
            'color' => $this->getQueueColor($item['key']),
        ], $items);
    }

    /**
     * Chart-ready data for the consolidated view: one independent bar per queue.
     *
     * An agent can be connected to several queues at the same time (queue membership
     * is not mutually exclusive like an agent status), so these bars are deliberately
     * rendered as independent, non-stacked values rather than summed into a single total.
     *
     * @return array{categories: array<int, string>, series: array<int, array<string, mixed>>, colors: array<int, string>}
     */
    public function getAggregateChartData(): array
    {
        $items = $this->getAggregateByQueue();

        return [
            'categories' => array_column($items, 'label'),
            'series' => [[
                'name' => __('expert-statistics::pbx.expert_statistics.agent_monitoring_col_duration'),
                'data' => array_column($items, 'minutes'),
            ]],
            'colors' => array_column($items, 'color'),
        ];
    }

    /**
     * Group the raw (date, user_dn, queue_dn) rows by agent, summed across days.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getPerAgentBreakdown(): array
    {
        return array_map(fn (array $agent): array => [
            'user_dn' => $agent['user_dn'],
            'queues' => array_map(fn (array $item): array => ['queue_dn' => $item['key'], 'total_minutes' => $item['minutes']], $agent['items']),
            'total_minutes' => $agent['total_minutes'],
        ], $this->groupedByAgent());
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

        return PbxDataProcessor::agentMonitoringBuildStackedSeries($buckets, $this->queueNameResolver(), $this->queueColorResolver());
    }

    /**
     * Categories + series (ApexCharts-ready) for the daily stacked bar chart:
     * total connected time per queue, per day, summed across the selected agents.
     *
     * Used for the consolidated view only — see getDailySeriesPerAgent() for the
     * per-agent breakdown shown in the detailed view.
     *
     * @return array{categories: array<int, string>, series: array<int, array<string, mixed>>}
     */
    public function getDailySeries(): array
    {
        $buckets = PbxDataProcessor::agentMonitoringGroupByDate($this->breakdown, 'report_date', 'queue_dn', $this->connectedMinutesResolver());

        return PbxDataProcessor::agentMonitoringBuildStackedSeries($buckets, $this->queueNameResolver(), $this->queueColorResolver());
    }

    /**
     * One daily stacked-bar series per agent: connected time per queue, per day,
     * for that agent alone.
     *
     * @return array<int, array{user_dn: string, categories: array<int, string>, series: array<int, array<string, mixed>>}>
     */
    public function getDailySeriesPerAgent(): array
    {
        $result = [];

        foreach (PbxDataProcessor::agentMonitoringDistinctKeys($this->breakdown, 'user_dn') as $dn) {
            $rows = array_values(array_filter($this->breakdown, fn (array $row): bool => (string) ($row['user_dn'] ?? '') === $dn));
            $buckets = PbxDataProcessor::agentMonitoringGroupByDate($rows, 'report_date', 'queue_dn', $this->connectedMinutesResolver());

            $result[] = ['user_dn' => $dn, ...PbxDataProcessor::agentMonitoringBuildStackedSeries($buckets, $this->queueNameResolver(), $this->queueColorResolver())];
        }

        return $result;
    }

    /**
     * ApexCharts heatmap series: one row per agent, one column per queue,
     * cell value = connected minutes for that agent/queue over the period.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getHeatmapSeries(): array
    {
        $keys = PbxDataProcessor::agentMonitoringDistinctKeys($this->breakdown, 'queue_dn');

        return PbxDataProcessor::agentMonitoringBuildHeatmapSeries(
            $this->groupedByAgent(),
            $keys,
            fn (string $dn): string => $this->getAgentLabel($dn),
            fn (string $queueDn): string => $this->getQueueLabel($queueDn),
        );
    }

    public function getQueueColor(string $queueDn): string
    {
        if ($this->queueColorMap === []) {
            $this->queueColorMap = $this->buildQueueColorMap();
        }

        return $this->queueColorMap[$queueDn] ?? '#9ca3af';
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
        return view('expert-statistics::livewire.agent-monitoring.queue-connection');
    }

    /** @return array<int, array{user_dn: string, items: array<int, array{key: string, minutes: int}>, total_minutes: int}> */
    private function groupedByAgent(): array
    {
        return PbxDataProcessor::agentMonitoringGroupByAgent($this->breakdown, 'user_dn', 'queue_dn', $this->connectedMinutesResolver());
    }

    private function connectedMinutesResolver(): \Closure
    {
        return fn (array $row): int => (int) round(($row['connected_seconds'] ?? 0) / 60);
    }

    private function queueNameResolver(): \Closure
    {
        return fn (string $queueDn): string => $this->getQueueLabel($queueDn);
    }

    private function queueColorResolver(): \Closure
    {
        return fn (string $queueDn): string => $this->getQueueColor($queueDn);
    }

    /**
     * Assign a stable color to every distinct queue_dn present in the loaded breakdown,
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

        $map = [];
        foreach (PbxDataProcessor::agentMonitoringDistinctKeys($this->breakdown, 'queue_dn') as $index => $queueDn) {
            $map[$queueDn] = $palette[$index % count($palette)];
        }

        return $map;
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

    private function loadNameMaps(): void
    {
        try {
            $map = app(ExpertStatisticsService::class)->getMap();
            $this->extensionsMap = $map['extensions'] ?? [];
            $this->queueNameMap = collect($map['call_queues'] ?? [])
                ->map(fn (mixed $q, string $dn): string => $dn.' — '.(is_array($q) ? ($q['name'] ?? $dn) : $dn))
                ->all();
        } catch (\Throwable) {
            $this->extensionsMap = [];
            $this->queueNameMap = [];
        }
    }
}
