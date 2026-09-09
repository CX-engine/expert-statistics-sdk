<?php

namespace CXEngine\ExpertStatistics\Livewire\Reports\MyUsers;

use Carbon\Carbon;
use CXEngine\ExpertStatistics\Concerns\AuthorizesExpertStatisticsAccess;
use CXEngine\ExpertStatistics\Concerns\HasPbxElementSelector;
use CXEngine\ExpertStatistics\Concerns\RequiresExpertStatisticsActivation;
use CXEngine\ExpertStatistics\Services\ExpertStatisticsService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

class MyUsersKpi extends Component
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

    #[Url]
    public string $granularity = 'day';

    public bool $excludeClosedHours = false;

    public bool $isLoading = false;

    /**
     * Per-DN processed data: [{dn, rows: [{period, answered, unanswered, total, rate, avg_wait}]}]
     *
     * @var array<int, array<string, mixed>>
     */
    public array $dnData = [];

    /** @var array<int, array<string, mixed>> */
    public array $consolidated = [];

    /** @var array<string, mixed> */
    public array $summary = [];

    public ?string $errorMessage = null;

    /** @var array<string, string> */
    public array $granularityOptions = [
        'hour' => 'expert-statistics::pbx.expert_statistics.kpi_granularity_hour',
        'day' => 'expert-statistics::pbx.expert_statistics.kpi_granularity_day',
        'weekday' => 'expert-statistics::pbx.expert_statistics.kpi_granularity_weekday',
        'week' => 'expert-statistics::pbx.expert_statistics.kpi_granularity_week',
        'month' => 'expert-statistics::pbx.expert_statistics.kpi_granularity_month',
    ];

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

    public function setGranularity(string $value): void
    {
        $this->granularity = $value;
        $this->loadData();
    }

    /** Called by Livewire after wire:model updates excludeClosedHours. */
    public function updatedExcludeClosedHours(): void
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        if (! $this->dn || ! $this->startDate || ! $this->endDate) {
            return;
        }

        $this->isLoading = true;
        $this->errorMessage = null;
        $this->dnData = [];
        $this->consolidated = [];
        $this->summary = [];

        try {
            $params = [
                'start_date' => $this->startDate,
                'end_date' => $this->endDate,
                'start_time' => $this->startTime,
                'end_time' => $this->endTime,
                'dn' => $this->dn,
                'exclude_closed_hours' => $this->excludeClosedHours ? 1 : 0,
            ];

            $raw = app(ExpertStatisticsService::class)->getKpi($this->urlType, $this->granularity, $params);
            $this->processKpiData($raw);
        } catch (\Throwable) {
            $this->errorMessage = 'Erreur lors du chargement des données.';
        }

        $this->isLoading = false;
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
        return view('expert-statistics::livewire.reports.my-users.kpi');
    }

    /**
     * @param  array<mixed>  $raw  [{dn, details: {period: {answered_calls, inbound_calls, waiting_time}}}]
     */
    private function processKpiData(array $raw): void
    {
        $totalAnswered = 0;
        $totalInbound = 0;
        $totalWaiting = 0.0;

        /** @var array<string, array{answered: int, unanswered: int, total: int, waiting: float}> $byPeriod */
        $byPeriod = [];

        foreach ($raw as $item) {
            $dn = (string) ($item['dn'] ?? '');
            $details = $item['details'] ?? [];
            $rows = [];

            foreach ($details as $period => $detail) {
                $answered = (int) ($detail['answered_calls'] ?? 0);
                $inbound = (int) ($detail['inbound_calls'] ?? 0);
                $waiting = (float) ($detail['waiting_time'] ?? 0);
                $unanswered = max(0, $inbound - $answered);
                $rate = $inbound > 0 ? round($answered / $inbound * 100, 1) : 0;
                $avgWait = $answered > 0 ? (int) round($waiting / $answered) : 0;

                $rows[] = [
                    'period' => (string) $period,
                    'answered' => $answered,
                    'unanswered' => $unanswered,
                    'total' => $inbound,
                    'rate' => $rate,
                    'avg_wait' => $avgWait,
                ];

                $totalAnswered += $answered;
                $totalInbound += $inbound;
                $totalWaiting += $waiting;

                if (! isset($byPeriod[(string) $period])) {
                    $byPeriod[(string) $period] = ['answered' => 0, 'unanswered' => 0, 'total' => 0, 'waiting' => 0.0];
                }
                $byPeriod[(string) $period]['answered'] += $answered;
                $byPeriod[(string) $period]['total'] += $inbound;
                $byPeriod[(string) $period]['unanswered'] += $unanswered;
                $byPeriod[(string) $period]['waiting'] += $waiting;
            }

            $isNumericGranularity = in_array($this->granularity, ['hour', 'weekday']);
            usort($rows, fn ($a, $b) => $isNumericGranularity
                ? (int) $a['period'] <=> (int) $b['period']
                : $a['period'] <=> $b['period']
            );
            foreach ($rows as &$row) {
                $row['period'] = $this->formatPeriodLabel($row['period']);
            }
            unset($row);

            $this->dnData[] = ['dn' => $dn, 'rows' => $rows];
        }

        if (in_array($this->granularity, ['hour', 'weekday'])) {
            ksort($byPeriod, SORT_NUMERIC);
        } else {
            ksort($byPeriod);
        }
        foreach ($byPeriod as $period => $totals) {
            $r = $totals['total'] > 0 ? round($totals['answered'] / $totals['total'] * 100, 1) : 0;
            $aw = $totals['answered'] > 0 ? (int) round($totals['waiting'] / $totals['answered']) : 0;
            $this->consolidated[] = [
                'period' => $this->formatPeriodLabel((string) $period),
                'answered' => $totals['answered'],
                'unanswered' => $totals['unanswered'],
                'total' => $totals['total'],
                'rate' => $r,
                'avg_wait' => $aw,
            ];
        }

        $totalUnanswered = max(0, $totalInbound - $totalAnswered);
        $this->summary = [
            'total' => $totalInbound,
            'answered' => $totalAnswered,
            'unanswered' => $totalUnanswered,
            'rate' => $totalInbound > 0 ? round($totalAnswered / $totalInbound * 100, 1) : 0,
            'avg_wait' => $totalAnswered > 0 ? (int) round($totalWaiting / $totalAnswered) : 0,
        ];
    }

    private function formatPeriodLabel(string $period): string
    {
        if ($this->granularity !== 'weekday') {
            return $period;
        }

        $day = (int) $period;
        if ($day < 1 || $day > 7) {
            return $period;
        }

        return ucfirst(
            Carbon::now()->startOfWeek(Carbon::MONDAY)->addDays($day - 1)->locale(app()->getLocale())->isoFormat('dddd')
        );
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
