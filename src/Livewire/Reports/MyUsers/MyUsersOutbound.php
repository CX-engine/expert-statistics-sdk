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

/**
 * Users' external outbound calls report: calls a user placed to an external
 * number. Internal calls (to another extension, a queue, an IVR...) are
 * never counted - see the API's UsersOutboundCallsController for the exact
 * definition.
 */
class MyUsersOutbound extends Component
{
    use AuthorizesExpertStatisticsAccess;
    use RequiresExpertStatisticsActivation;
    use HasPbxElementSelector;

    /** Scheduled/sent reports of this page are stored under this report_type. */
    public const REPORT_TYPE = 'userOutboundReport';

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

    public bool $excludeClosedHours = false;

    public bool $isLoading = false;

    public string $sortField = '';

    public string $sortDirection = 'asc';

    /** @var array<int, array<string, mixed>> */
    public array $tableData = [];

    /** @var array<string, mixed> */
    public array $aggregated = [];

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

    /** Called by Livewire after wire:model updates excludeClosedHours. */
    public function updatedExcludeClosedHours(): void
    {
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
            reportType: self::REPORT_TYPE,
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
            reportType: self::REPORT_TYPE,
        );
    }

    /** Points at UsersOutboundReportExportController's route, registered by the host app. */
    public function getExportUrl(): string
    {
        if (! $this->dn || ! $this->startDate || ! $this->endDate || ! Route::has('expert-stats.my-users.outbound-export')) {
            return '#';
        }

        return route('expert-stats.my-users.outbound-export', $this->query());
    }

    public function loadData(): void
    {
        if (! $this->dn || ! $this->startDate || ! $this->endDate) {
            return;
        }

        $this->isLoading = true;
        $this->errorMessage = null;
        $this->tableData = [];
        $this->aggregated = [];

        try {
            $raw = app(ExpertStatisticsService::class)->getUsersOutboundReport($this->query());
            $this->tableData = $raw['report'] ?? [];
            $this->aggregated = $raw['aggregated'] ?? [];
        } catch (\Throwable) {
            $this->errorMessage = __('expert-statistics::pbx.expert_statistics.error_loading');
        }

        $this->isLoading = false;
    }

    /** @return array<int, array<string, mixed>> */
    public function getDisplayData(): array
    {
        if ($this->sortField === '') {
            return $this->tableData;
        }

        $field = $this->sortField;
        $dir = $this->sortDirection === 'asc' ? 1 : -1;
        $value = fn (array $row): float|string => $field === 'user' ? (string) ($row['user'] ?? '') : (float) ($row[$field] ?? 0);

        $data = $this->tableData;
        usort($data, fn (array $a, array $b): int => ($value($a) <=> $value($b)) * $dir);

        return $data;
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
        return view('expert-statistics::livewire.reports.my-users.outbound');
    }

    /** @return array<string, mixed> */
    private function query(): array
    {
        return [
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
            'dn' => $this->dn,
            'exclude_closed_hours' => $this->excludeClosedHours ? 1 : 0,
        ];
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
