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

class MyUsersOrigins extends Component
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

    /** Whether to show top-10 individual origins instead of origin-type families */
    public bool $isTop10 = false;

    /** Whether to hide destinations with zero calls */
    public bool $hideEmpty = true;

    public bool $excludeClosedHours = false;

    public bool $isLoading = false;

    /**
     * Per-destination rows: [{dst_dn, details: [{origin_dn_type, inbound_calls, origin_dn?, origin_display_name?}]}]
     *
     * @var array<int, array<string, mixed>>
     */
    public array $originData = [];

    /** @var array<int, array<string, mixed>> */
    public array $consolidated = [];

    public ?string $errorMessage = null;

    /** @var array<int, string> */
    public array $originTypeLabels = [
        0 => 'Interne',
        1 => 'Externe',
        2 => 'Indéfini',
        4 => 'File',
        5 => 'Messagerie',
        6 => 'SVI',
        8 => 'Indéfini',
        13 => 'Externe (nbre)',
        14 => 'Call Flow',
        148 => 'Call Flow',
        404 => 'Call Flow',
        999 => 'Inconnu',
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

    public function toggleTop10(): void
    {
        $this->isTop10 = ! $this->isTop10;
        $this->loadData();
    }

    public function toggleHideEmpty(): void
    {
        $this->hideEmpty = ! $this->hideEmpty;
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
        $this->originData = [];
        $this->consolidated = [];

        try {
            $params = [
                'start_date' => $this->startDate,
                'end_date' => $this->endDate,
                'start_time' => $this->startTime,
                'end_time' => $this->endTime,
                'dn' => $this->dn,
                'exclude_closed_hours' => $this->excludeClosedHours ? 1 : 0,
            ];

            $raw = app(ExpertStatisticsService::class)->getOrigin($this->urlType, $params, $this->isTop10);

            $this->originData = array_values($raw['data'] ?? []);
            $this->consolidated = $raw['consolidated'] ?? [];
        } catch (\Throwable) {
            $this->errorMessage = 'Erreur lors du chargement des données.';
        }

        $this->isLoading = false;
    }

    public function getOriginLabel(int $type): string
    {
        return $this->originTypeLabels[$type] ?? "Type {$type}";
    }

    public function render(): View
    {
        return view('expert-statistics::livewire.reports.my-users.origins');
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
