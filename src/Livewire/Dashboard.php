<?php

namespace CXEngine\ExpertStatistics\Livewire;

use Carbon\Carbon;
use CXEngine\ExpertStatistics\Concerns\AuthorizesExpertStatisticsAccess;
use CXEngine\ExpertStatistics\Services\ExpertStatisticsService;
use CXEngine\ExpertStatistics\Support\PbxDataProcessor;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * PBX call statistics dashboard, ported from bluerocktelclients'
 * App\Filament\Resources\Dashboard\Pages\PbxDashboard plus its six
 * App\Filament\Widgets\Pbx\* widgets. Unlike the source app, this is a
 * single plain Livewire component (no Filament page, no widget bus) —
 * each former widget's loadData() logic is folded into a dedicated
 * load*() method below, all driven by one filter state and called
 * together from loadData().
 */
class Dashboard extends Component
{
    use AuthorizesExpertStatisticsAccess;

    #[Url]
    public string $tab = 'period';

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
    public ?string $urlType = null;

    #[Url]
    public array $selectedElements = [];

    #[Url]
    public bool $uniqueCalls = false;

    public bool $showDatePicker = false;

    /**
     * Never surfaced in the UI (the source app never wired a toggle for it
     * either — see PbxDashboard::getFilterData(), which omits the key), kept
     * only because the relay query accepts it.
     */
    public bool $excludeClosedHours = false;

    public string $inboundChartView = 'hour';

    /** @var array<string, mixed> */
    public array $inboundKpis = [];

    /** @var array<string, mixed> */
    public array $inboundChartByHour = [];

    /** @var array<string, mixed> */
    public array $inboundChartByDay = [];

    public string $outboundChartView = 'hour';

    /** @var array<string, mixed> */
    public array $outboundKpis = [];

    /** @var array<string, mixed> */
    public array $outboundChartByHour = [];

    /** @var array<string, mixed> */
    public array $outboundChartByDay = [];

    /** @var array<string, mixed> */
    public array $topUsersChartData = [];

    public bool $showDuration = false;

    /** @var array<string, mixed> */
    public array $totals = [];

    /** @var array<string, mixed> */
    public array $totalsChartData = [];

    /** @var array<string, mixed> */
    public array $answeredChart = [];

    /** @var array<string, mixed> */
    public array $waitTimeChart = [];

    /** @var array<string, mixed> */
    public array $trends = [];

    public function mount(): void
    {
        $this->applyPeriod($this->selectedPeriod);
        $this->loadData();
    }

    public function selectPeriod(string $value): void
    {
        $this->selectedPeriod = $value;
        $this->applyPeriod($value);
        $this->loadData();
    }

    public function setCustomRange(string $start, string $end): void
    {
        $this->startDate = $start;
        $this->endDate = $end;
        $this->selectedPeriod = 'custom';
        $this->showDatePicker = false;
        $this->loadData();
    }

    public function switchTab(string $tab): void
    {
        $this->tab = $tab;
    }

    /** Opens CXEngine\ExpertStatistics\Livewire\Reports\ShareReportModal in "send" mode. */
    public function shareReport(): void
    {
        $this->dispatch('open-share-report',
            startDate: $this->startDate,
            endDate: $this->endDate,
            startTime: $this->startTime,
            endTime: $this->endTime,
        );
    }

    /** Opens CXEngine\ExpertStatistics\Livewire\Reports\ShareReportModal in "schedule" mode. */
    public function scheduleReport(): void
    {
        $this->dispatch('open-schedule-report',
            startDate: $this->startDate,
            endDate: $this->endDate,
            startTime: $this->startTime,
            endTime: $this->endTime,
        );
    }

    public function updateTimeRange(): void
    {
        $this->loadData();
    }

    public function setInboundChartView(string $view): void
    {
        $this->inboundChartView = $view;
    }

    public function setOutboundChartView(string $view): void
    {
        $this->outboundChartView = $view;
    }

    public function toggleDuration(): void
    {
        $this->showDuration = ! $this->showDuration;
    }

    public function loadData(): void
    {
        $this->loadInbound();
        $this->loadOutbound();
        $this->loadTopUsers();
        $this->loadTotals();
        $this->loadTrendCharts();
        $this->loadTrendKpi();
    }

    public function loadInbound(): void
    {
        if (! $this->startDate || ! $this->endDate) {
            return;
        }

        try {
            $type = $this->urlType ?? 'queue';
            $dn = ! empty($this->selectedElements) ? implode(',', $this->selectedElements) : null;
            $preAnswer = ! $this->uniqueCalls && $this->urlType !== 'extension';

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

            $raw = app(ExpertStatisticsService::class)->getInboundCalls($type, $query, $preAnswer);

            $grouped = PbxDataProcessor::groupInboundByDayAndHour($raw);

            $this->inboundKpis = PbxDataProcessor::calcInboundKpis($grouped);
            $this->inboundChartByDay = PbxDataProcessor::buildAnsweredChartByDay($grouped['byDay'], $this->startDate, $this->endDate);
            $this->inboundChartByHour = PbxDataProcessor::buildAnsweredChartByHour($grouped['byHour'], $this->startTime, $this->endTime);
        } catch (\Throwable) {
            $this->inboundKpis = [];
            $this->inboundChartByDay = [];
            $this->inboundChartByHour = [];
        }
    }

    public function loadOutbound(): void
    {
        if (! $this->startDate || ! $this->endDate || $this->urlType !== null) {
            return;
        }

        try {
            $query = [
                'start_date' => $this->startDate,
                'end_date' => $this->endDate,
                'start_time' => $this->startTime,
                'end_time' => $this->endTime,
                'exclude_closed_hours' => $this->excludeClosedHours ? 1 : 0,
            ];

            $raw = app(ExpertStatisticsService::class)->getOutboundCalls($query);

            $grouped = PbxDataProcessor::groupOutboundByDayAndHour($raw);

            $this->outboundKpis = PbxDataProcessor::calcOutboundKpis($grouped);
            $this->outboundChartByDay = PbxDataProcessor::buildOutboundChartByDay($grouped['byDay'], $this->startDate, $this->endDate);
            $this->outboundChartByHour = PbxDataProcessor::buildOutboundChartByHour($grouped['byHour'], $this->startTime, $this->endTime);
        } catch (\Throwable) {
            $this->outboundKpis = [];
            $this->outboundChartByDay = [];
            $this->outboundChartByHour = [];
        }
    }

    public function loadTopUsers(): void
    {
        if (! $this->startDate || ! $this->endDate || $this->urlType === 'queue') {
            return;
        }

        try {
            $dn = ! empty($this->selectedElements) ? implode(',', $this->selectedElements) : null;

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

            $raw = app(ExpertStatisticsService::class)->getUsersCalls($query);

            $this->topUsersChartData = PbxDataProcessor::buildTopUsersChart($raw);
        } catch (\Throwable) {
            $this->topUsersChartData = [];
        }
    }

    public function loadTotals(): void
    {
        if (! $this->startDate || ! $this->endDate || $this->urlType !== null) {
            return;
        }

        try {
            $query = [
                'start_date' => $this->startDate,
                'end_date' => $this->endDate,
                'start_time' => $this->startTime,
                'end_time' => $this->endTime,
                'exclude_closed_hours' => $this->excludeClosedHours ? 1 : 0,
            ];

            $raw = app(ExpertStatisticsService::class)->getUniqueCallsPeriod($query);

            $this->totals = PbxDataProcessor::parsePbxTotals($raw);

            $this->totalsChartData = [
                'series' => [
                    $this->totals['inbound']['calls'],
                    $this->totals['outbound']['calls'],
                    $this->totals['internal']['calls'],
                ],
                'labels' => ['Inbound', 'Outbound', 'Internal'],
                'colors' => ['#0EA5E9', '#00E396', '#eab308'],
            ];
        } catch (\Throwable $e) {
            report($e);

            $this->totals = [];
            $this->totalsChartData = [];
        }
    }

    public function loadTrendCharts(): void
    {
        try {
            $query = [
                'start_time' => $this->startTime,
                'end_time' => $this->endTime,
                'exclude_closed_hours' => $this->excludeClosedHours ? 1 : 0,
            ];

            $raw = app(ExpertStatisticsService::class)->getTrendMonthly($query);
            $charts = PbxDataProcessor::buildTrendCharts($raw);
            $this->answeredChart = $charts['answered'];
            $this->waitTimeChart = $charts['waitTime'];
        } catch (\Throwable) {
            $this->answeredChart = [];
            $this->waitTimeChart = [];
        }
    }

    public function loadTrendKpi(): void
    {
        try {
            $query = [
                'start_time' => $this->startTime,
                'end_time' => $this->endTime,
                'exclude_closed_hours' => $this->excludeClosedHours ? 1 : 0,
            ];

            $raw = app(ExpertStatisticsService::class)->getTrendMonthly($query);
            $this->trends = PbxDataProcessor::calcTrends($raw);
        } catch (\Throwable) {
            $this->trends = [];
        }
    }

    public function render(): View
    {
        return view('expert-statistics::livewire.dashboard');
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
            'last3Months' => $this->setRange($today->copy()->subMonths(3), $today->copy()),
            'last6Months' => $this->setRange($today->copy()->subMonths(6), $today->copy()),
            default => null,
        };
    }

    private function setRange(Carbon $start, Carbon $end): void
    {
        $this->startDate = $start->format('Y-m-d');
        $this->endDate = $end->format('Y-m-d');
    }
}
