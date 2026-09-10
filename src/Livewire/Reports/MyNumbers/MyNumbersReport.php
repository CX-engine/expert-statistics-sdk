<?php

namespace CXEngine\ExpertStatistics\Livewire\Reports\MyNumbers;

use Carbon\Carbon;
use CXEngine\ExpertStatistics\Concerns\AuthorizesExpertStatisticsAccess;
use CXEngine\ExpertStatistics\Concerns\HasPbxElementSelector;
use CXEngine\ExpertStatistics\Concerns\RequiresExpertStatisticsActivation;
use CXEngine\ExpertStatistics\Services\ExpertStatisticsService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

class MyNumbersReport extends Component
{
    use AuthorizesExpertStatisticsAccess;
    use RequiresExpertStatisticsActivation;
    use HasPbxElementSelector;

    public string $urlType = 'did';

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

    public bool $groupData = true;

    public bool $didsOnlyStats = true;

    public bool $queuesOnlyStats = true;

    public bool $excludeClosedHours = false;

    public bool $isLoading = false;

    public string $sortField = '';

    public string $sortDirection = 'asc';

    /** @var array<int, array<string, mixed>> */
    public array $didData = [];

    /** @var array<int, array<string, mixed>> */
    public array $queueData = [];

    /** @var array<int, array<string, mixed>> */
    public array $extensionData = [];

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

    public function toggleGroupData(): void
    {
        $this->groupData = ! $this->groupData;
        $this->loadData();
    }

    public function toggleDidsOnlyStats(): void
    {
        $this->didsOnlyStats = ! $this->didsOnlyStats;
        $this->queuesOnlyStats = true;
        $this->loadData();
    }

    public function toggleQueuesOnlyStats(): void
    {
        $this->queuesOnlyStats = ! $this->queuesOnlyStats;
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

    public function loadData(): void
    {
        if (! $this->dn || ! $this->startDate || ! $this->endDate) {
            return;
        }

        $this->isLoading = true;
        $this->errorMessage = null;
        $this->didData = [];
        $this->queueData = [];
        $this->extensionData = [];

        try {
            $params = [
                'start_date' => $this->startDate,
                'end_date' => $this->endDate,
                'start_time' => $this->startTime,
                'end_time' => $this->endTime,
                'dn' => $this->dn,
                'grouped' => $this->groupData ? 1 : 0,
                'exclude_closed_hours' => $this->excludeClosedHours ? 1 : 0,
            ];

            $raw = app(ExpertStatisticsService::class)->getDidReport($params);

            $this->didData = $raw['did'] ?? [];
            $queueReport = $raw['queue'] ?? [];

            foreach ($queueReport as $row) {
                $keyType = $row['keyType'] ?? null;
                if ($keyType == 4 && ($row['queue'] ?? null) == ($row['element'] ?? null)) {
                    $this->queueData[] = $row;
                }
                if ($keyType == 4 && ($row['queue'] ?? null) == ($row['element'] ?? null)) {
                    $this->extensionData[] = $row;
                }
                if ($keyType == 0) {
                    $this->extensionData[] = $row;
                }
            }
        } catch (\Throwable $e) {
            $this->errorMessage = 'Erreur lors du chargement des données.';
        }

        $this->isLoading = false;
    }

    /** @return array<int, array<string, mixed>> */
    public function getDisplayData(): array
    {
        if ($this->didsOnlyStats) {
            $data = $this->didData;
        } else {
            $data = $this->queuesOnlyStats ? $this->queueData : $this->extensionData;
        }

        if ($this->sortField !== '') {
            $data = $this->sortRows($data);
        }

        return array_values($data);
    }

    public function formatDate(string $day): string
    {
        if (strlen($day) === 8) {
            return substr($day, 6, 2).'/'.substr($day, 4, 2).'/'.substr($day, 0, 4);
        }

        return $day;
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

    public function formatPercentage(mixed $value): string
    {
        return number_format((float) $value, 1).'%';
    }

    public function render(): View
    {
        return view('expert-statistics::livewire.reports.my-numbers.report');
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
                'declined' => (float) max(0,
                    (int) ($row['calls'] ?? $row['calls_ext'] ?? 0) - (int) ($row['answered'] ?? 0) - (int) ($row['abandoned'] ?? 0)
                ),
                'answered_percentage' => (function () use ($row): float {
                    $calls = (int) ($row['calls'] ?? $row['calls_ext'] ?? 0);
                    $divisor = $calls - (int) ($row['preanswer_abandoned'] ?? 0);

                    return $divisor > 0 ? ((int) ($row['answered'] ?? 0) / $divisor) * 100 : 0.0;
                })(),
                'day', 'did', 'queueNameNumber', 'elementNameNumber' => (string) ($row[$field] ?? ''),
                default => (float) ($row[$field] ?? 0),
            };
        };

        // In extension view keep queue rows at the top, sort extension rows separately
        if (! $this->didsOnlyStats && ! $this->queuesOnlyStats) {
            $queues = array_values(array_filter($data, fn (array $r): bool => ($r['keyType'] ?? null) == 4));
            $exts = array_values(array_filter($data, fn (array $r): bool => ($r['keyType'] ?? null) == 0));

            usort($queues, fn ($a, $b): int => ($getValue($a) <=> $getValue($b)) * $dir);
            usort($exts, fn ($a, $b): int => ($getValue($a) <=> $getValue($b)) * $dir);

            return [...$queues, ...$exts];
        }

        usort($data, fn ($a, $b): int => ($getValue($a) <=> $getValue($b)) * $dir);

        return $data;
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
