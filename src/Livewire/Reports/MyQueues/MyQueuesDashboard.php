<?php

namespace CXEngine\ExpertStatistics\Livewire\Reports\MyQueues;

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
 * Queues dashboard, ported from bluerocktelclients'
 * App\Filament\Resources\ExpertStatistics\MyQueues\Pages\MyQueuesDashboard
 * plus the App\Filament\Widgets\Pbx\{InboundCalls,TrendKpi,TrendCharts}Widget
 * it embedded. Unlike the source app, this is a single plain Livewire
 * component (no Filament page, no widget bus, no sub-navigation) — each
 * former widget's loadData() logic is folded into a dedicated load*()
 * method below, all driven by this component's own filter state and
 * called together from loadData(), mirroring the sibling
 * CXEngine\ExpertStatistics\Livewire\Dashboard component's approach.
 */
class MyQueuesDashboard extends Component
{
    use AuthorizesExpertStatisticsAccess;
    use RequiresExpertStatisticsActivation;
    use HasPbxElementSelector;

    public string $urlType = 'queue';

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

    /** hour|day view toggle for the inbound calls chart */
    public string $chartView = 'hour';

    /** @var array<string, mixed> */
    public array $kpis = [];

    /** @var array<string, mixed> */
    public array $chartByHour = [];

    /** @var array<string, mixed> */
    public array $chartByDay = [];

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

    public function setChartView(string $view): void
    {
        $this->chartView = $view;
    }

    public function updateTimeRange(): void
    {
        $this->saveExpertStatsTime();
        $this->loadData();
    }

    public function toggleUniqueCalls(): void
    {
        $this->uniqueCalls = ! $this->uniqueCalls;
        $this->loadData();
    }

    /** Called by Livewire after wire:model updates uniqueCalls. */
    public function updatedUniqueCalls(): void
    {
        $this->loadData();
    }

    /** Called by Livewire after wire:model updates excludeClosedHours. */
    public function updatedExcludeClosedHours(): void
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        $this->loadInboundCalls();
    }

    public function loadInboundCalls(): void
    {
        if (! $this->startDate || ! $this->endDate) {
            $this->kpis = [];
            $this->chartByDay = [];
            $this->chartByHour = [];

            return;
        }

        try {
            $dn = ! empty($this->selectedElements) ? implode(',', $this->selectedElements) : null;
            $preAnswer = ! $this->uniqueCalls;

            $query = [
                'start_date' => $this->startDate,
                'end_date' => $this->endDate,
                'start_time' => $this->startTime,
                'end_time' => $this->endTime,
                'exclude_closed_hours' => $this->excludeClosedHours ? 1 : 0,
            ];

            if ($dn !== null) {
                $query['dn'] = $dn;
            }

            $raw = app(ExpertStatisticsService::class)->getInboundCalls('queue', $query, $preAnswer);

            $grouped = PbxDataProcessor::groupInboundByDayAndHour($raw);

            $this->kpis = PbxDataProcessor::calcInboundKpis($grouped);
            $this->chartByDay = PbxDataProcessor::buildAnsweredChartByDay($grouped['byDay'], $this->startDate, $this->endDate);
            $this->chartByHour = PbxDataProcessor::buildAnsweredChartByHour($grouped['byHour'], $this->startTime, $this->endTime);
        } catch (\Throwable) {
            $this->kpis = [];
            $this->chartByDay = [];
            $this->chartByHour = [];
        }
    }

    /**
     * Reshape the period tab's answered/unanswered chart series (categories +
     * series) into the {period, answered, unanswered, total, rate, avg_wait}
     * row shape expected by the reusable charts.kpi-chart component.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getInboundRows(): array
    {
        $chart = $this->chartView === 'hour' ? $this->chartByHour : $this->chartByDay;
        $categories = $chart['categories'] ?? [];
        $series = collect($chart['series'] ?? []);
        $answered = $series->firstWhere('name', 'Answered')['data'] ?? [];
        $unanswered = $series->firstWhere('name', 'Unanswered')['data'] ?? [];

        $rows = [];

        foreach ($categories as $i => $category) {
            $ans = (int) ($answered[$i] ?? 0);
            $unans = (int) ($unanswered[$i] ?? 0);
            $total = $ans + $unans;

            $rows[] = [
                'period' => (string) $category,
                'answered' => $ans,
                'unanswered' => $unans,
                'total' => $total,
                'rate' => $total > 0 ? round($ans / $total * 100, 1) : 0,
                'avg_wait' => 0,
            ];
        }

        return $rows;
    }

    public function render(): View
    {
        return view('expert-statistics::livewire.reports.my-queues.dashboard');
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
