<?php

namespace CXEngine\ExpertStatistics\Livewire\Reports\MyQueues;

use Carbon\Carbon;
use CXEngine\ExpertStatistics\Concerns\AuthorizesExpertStatisticsAccess;
use CXEngine\ExpertStatistics\Concerns\HasPbxElementSelector;
use CXEngine\ExpertStatistics\Concerns\RequiresExpertStatisticsActivation;
use CXEngine\ExpertStatistics\Services\ExpertStatisticsService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Detailed queues report, ported from bluerocktelclients'
 * App\Filament\Resources\ExpertStatistics\MyQueues\Pages\MyQueuesReport.
 * Plain Livewire full-page component: no Filament page, no sub-navigation,
 * no report scheduling/sharing/export UI (out of scope for this package).
 */
class MyQueuesReport extends Component
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

    public bool $groupData = true;

    public bool $queuesOnlyStats = true;

    public bool $uniqueCalls = false;

    public bool $consolidateData = false;

    public bool $excludeClosedHours = false;

    public bool $isLoading = false;

    public string $sortField = '';

    public string $sortDirection = 'asc';

    public string $filterQueue = '';

    public string $filterExtension = '';

    /** @var array<int, array<string, mixed>> */
    public array $queueData = [];

    /** @var array<int, array<string, mixed>> */
    public array $extensionData = [];

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

    public function toggleGroupData(): void
    {
        $this->groupData = ! $this->groupData;
        if (! $this->groupData) {
            $this->consolidateData = false;
        }
        $this->loadData();
    }

    public function toggleQueuesOnlyStats(): void
    {
        $this->queuesOnlyStats = ! $this->queuesOnlyStats;
    }

    public function toggleUniqueCalls(): void
    {
        $this->uniqueCalls = ! $this->uniqueCalls;
    }

    /** Called by Livewire after wire:model updates consolidateData. */
    public function updatedConsolidateData(): void
    {
        if ($this->consolidateData && ! $this->groupData) {
            $this->groupData = true;
            $this->loadData();
        }
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
        $this->queueData = [];
        $this->extensionData = [];
        $this->aggregated = [];

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

            $raw = app(ExpertStatisticsService::class)->getQueuesReport($params);
            $this->aggregated = $raw['aggregated'] ?? [];

            foreach ($raw['report'] ?? [] as $row) {
                $keyType = $row['keyType'] ?? null;

                if ($keyType == 4 && ($row['queue'] ?? null) == ($row['element'] ?? null)) {
                    $this->queueData[] = $row;
                    $this->extensionData[] = $row;
                }
                if ($keyType == 0) {
                    $this->extensionData[] = $row;
                }
            }
        } catch (\Throwable) {
            $this->errorMessage = 'Erreur lors du chargement des données.';
        }

        $this->isLoading = false;
    }

    /** @return array<int, array<string, mixed>> */
    public function getDisplayData(): array
    {
        if ($this->consolidateData && $this->queuesOnlyStats) {
            return $this->getConsolidatedQueueData();
        }

        $data = $this->queuesOnlyStats ? $this->queueData : $this->extensionData;

        // Filter by queue name/number
        if ($this->filterQueue !== '') {
            $needle = mb_strtolower($this->filterQueue);
            $data = array_values(array_filter($data, fn (array $r): bool => str_contains(mb_strtolower((string) ($r['queueNameNumber'] ?? '')), $needle)
            ));
        }

        // Filter by extension name/number (extension view only; keeps queue header rows)
        if (! $this->queuesOnlyStats && $this->filterExtension !== '') {
            $needle = mb_strtolower($this->filterExtension);
            $data = array_values(array_filter($data, fn (array $r): bool => ($r['keyType'] ?? null) == 4 ||
                str_contains(mb_strtolower((string) ($r['elementNameNumber'] ?? '')), $needle)
            ));
        }

        if ($this->sortField !== '') {
            $data = $this->sortRows($data);
        }

        return array_values($data);
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

    public function formatDate(string $day): string
    {
        if (strlen($day) === 8) {
            return substr($day, 6, 2).'/'.substr($day, 4, 2).'/'.substr($day, 0, 4);
        }

        return $day;
    }

    public function render(): View
    {
        return view('expert-statistics::livewire.reports.my-queues.report');
    }

    /** @return array<int, array<string, mixed>> */
    private function getConsolidatedQueueData(): array
    {
        if (empty($this->queueData)) {
            return [];
        }

        $ignoreKeys = ['element', 'elementName', 'elementNameNumber', 'keyType', 'queue', 'queueName', 'queueNameNumber'];
        $holder = [];

        foreach ($this->queueData as $row) {
            foreach ($row as $key => $value) {
                if (\in_array($key, $ignoreKeys)) {
                    continue;
                }
                if (\array_key_exists($key, $holder)) {
                    $holder[$key] = (float) $holder[$key] + (float) $value;
                } else {
                    $holder[$key] = (float) $value;
                }
            }
        }

        if ($this->uniqueCalls) {
            $holder['calls'] = (float) ($this->aggregated['inbound_calls'] ?? 0);
            $holder['answered'] = (float) ($this->aggregated['answered_calls'] ?? 0);
            $holder['solicited'] = (float) ($this->aggregated['inbound_calls'] ?? 0);
        }

        $holder['queueNameNumber'] = 'Consolidée';
        $holder['keyType'] = 4;

        return [$holder];
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
                    (int) ($row['calls'] ?? 0) - (int) ($row['answered'] ?? 0) - (int) ($row['abandoned'] ?? 0)
                ),
                'answered_percentage' => (function () use ($row): float {
                    $divisor = (int) ($row['calls'] ?? 0) - (int) ($row['preanswer_abandoned'] ?? 0);

                    return $divisor > 0 ? ((int) ($row['answered'] ?? 0) / $divisor) * 100 : 0.0;
                })(),
                'day', 'queueNameNumber', 'elementNameNumber' => (string) ($row[$field] ?? ''),
                default => (float) ($row[$field] ?? 0),
            };
        };

        // In extension view keep queue rows at the top, sort extension rows separately
        if (! $this->queuesOnlyStats) {
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
