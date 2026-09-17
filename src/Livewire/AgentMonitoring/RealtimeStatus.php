<?php

namespace CXEngine\ExpertStatistics\Livewire\AgentMonitoring;

use CXEngine\ExpertStatistics\Concerns\AuthorizesExpertStatisticsAccess;
use CXEngine\ExpertStatistics\Concerns\RequiresExpertStatisticsActivation;
use CXEngine\ExpertStatistics\Services\ExpertStatisticsService;
use CXEngine\ExpertStatistics\Support\PbxDataProcessor;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Live agent presence/status board, ported from bluerocktelclients'
 * App\Filament\Pages\AgentMonitoring\RealtimeStatusPage. Plain Livewire
 * full-page component: no Filament page, no per-user session PBX host
 * (host is resolved via ResolvesActivePbxHost, same as every other page in
 * this package).
 */
class RealtimeStatus extends Component
{
    use AuthorizesExpertStatisticsAccess;
    use RequiresExpertStatisticsActivation;

    public string $searchTerm = '';

    public string $statusFilter = '';

    public string $registrationFilter = '';

    public string $sortKey = 'registered_duration';

    public bool $sortDesc = false;

    public bool $showAlerts = false;

    /** @var string[] */
    public array $dismissedAlertIds = [];

    /** @var array<int, array<string, mixed>> */
    public array $agentData = [];

    /** @var array<string, string> */
    public array $extensionsMap = [];

    public ?string $lastUpdated = null;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        $this->loadData();
    }

    /** Bound to wire:poll.60s in the view - mirrors the source page's refresh interval. */
    public function poll(): void
    {
        $this->loadData();
    }

    public function toggleAlerts(): void
    {
        $this->showAlerts = ! $this->showAlerts;
    }

    public function dismissAlert(string $alertId): void
    {
        if (! in_array($alertId, $this->dismissedAlertIds, true)) {
            $this->dismissedAlertIds[] = $alertId;
        }
    }

    public function sortBy(string $key): void
    {
        if ($this->sortKey === $key) {
            $this->sortDesc = ! $this->sortDesc;
        } else {
            $this->sortKey = $key;
            $this->sortDesc = false;
        }
    }

    /** @return array{online: int, away: int, unavailable: int, total: int} */
    public function getTotalCounts(): array
    {
        return PbxDataProcessor::agentMonitoringTotalCounts($this->agentData);
    }

    /** @return array<int, array<string, mixed>> */
    public function getTableData(): array
    {
        $data = $this->agentData;

        if ($this->searchTerm !== '') {
            $term = strtolower($this->searchTerm);
            $map = $this->extensionsMap;
            $data = array_values(array_filter($data, function (array $agent) use ($term, $map): bool {
                $dn = strtolower((string) ($agent['user_dn'] ?? ''));
                $name = strtolower((string) ($map[$agent['user_dn'] ?? ''] ?? ''));

                return str_contains($dn, $term) || str_contains($name, $term);
            }));
        }

        if ($this->statusFilter !== '') {
            $data = array_values(array_filter($data, function (array $agent): bool {
                $registered = $agent['registration_status']['pbx_registered'] ?? false;
                $code = (int) ($agent['current_status']['code'] ?? -1);

                return match ($this->statusFilter) {
                    'available' => $registered && $code === 0,
                    'away' => $registered && $code === 4,
                    'unavailable' => ! $registered || ($registered && $code !== 0),
                    default => true,
                };
            }));
        }

        if ($this->registrationFilter !== '') {
            $wantRegistered = $this->registrationFilter === 'true';
            $data = array_values(array_filter(
                $data,
                fn (array $agent): bool => ($agent['registration_status']['pbx_registered'] ?? false) === $wantRegistered,
            ));
        }

        usort($data, function (array $a, array $b): int {
            [$aVal, $bVal] = match ($this->sortKey) {
                'registered_duration' => [
                    (int) ($a['registration_status']['duration']['minutes'] ?? 0),
                    (int) ($b['registration_status']['duration']['minutes'] ?? 0),
                ],
                'status_duration' => [
                    (int) ($a['status_duration']['minutes'] ?? 0),
                    (int) ($b['status_duration']['minutes'] ?? 0),
                ],
                default => [
                    (string) ($a['user_dn'] ?? ''),
                    (string) ($b['user_dn'] ?? ''),
                ],
            };

            return $this->sortDesc ? $bVal <=> $aVal : $aVal <=> $bVal;
        });

        return array_values($data);
    }

    /** @return array<int, array{id: string, type: string, severity: string, title: string, message: string}> */
    public function getAlerts(): array
    {
        $alerts = PbxDataProcessor::agentMonitoringAlerts(
            $this->agentData,
            $this->extensionsMap,
            $this->dismissedAlertIds,
            __('expert-statistics::pbx.expert_statistics.agent_monitoring_unknown'),
        );

        return array_map(fn (array $alert): array => [
            'id' => $alert['id'],
            'type' => $alert['type'],
            'severity' => $alert['severity'],
            'title' => __($alert['titleKey']),
            'message' => __($alert['messageKey'], $alert['messageParams']),
        ], $alerts);
    }

    public function getAgentLabel(string $dn): string
    {
        $name = $this->extensionsMap[$dn] ?? null;

        return $name !== null ? "{$dn} — {$name}" : $dn;
    }

    /** @param  array<string, mixed>  $agent */
    public function getStatusBadgeClass(array $agent): string
    {
        $registered = $agent['registration_status']['pbx_registered'] ?? false;
        $code = (int) ($agent['current_status']['code'] ?? -1);

        if (! $registered) {
            return 'bg-red-100 text-red-700 dark:bg-red-950/40 dark:text-red-400';
        }

        return match ($code) {
            0 => 'bg-green-100 text-green-700 dark:bg-green-950/40 dark:text-green-400',
            4 => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-950/40 dark:text-yellow-400',
            default => 'bg-red-100 text-red-700 dark:bg-red-950/40 dark:text-red-400',
        };
    }

    /** @param  array<string, mixed>  $agent */
    public function getStatusDotClass(array $agent): string
    {
        $registered = $agent['registration_status']['pbx_registered'] ?? false;
        $code = (int) ($agent['current_status']['code'] ?? -1);

        if (! $registered) {
            return 'bg-red-500';
        }

        return match ($code) {
            0 => 'bg-green-500',
            4 => 'bg-yellow-400',
            default => 'bg-red-500',
        };
    }

    /** @param  array<string, mixed>  $agent */
    public function getRegistrationBadgeClass(array $agent): string
    {
        $registered = $agent['registration_status']['pbx_registered'] ?? false;
        $code = (int) ($agent['current_status']['code'] ?? -1);

        if ($registered && $code === 0) {
            return 'bg-green-100 text-green-700 dark:bg-green-950/40 dark:text-green-400';
        }

        if ($registered) {
            return 'bg-yellow-100 text-yellow-700 dark:bg-yellow-950/40 dark:text-yellow-400';
        }

        return 'bg-red-100 text-red-700 dark:bg-red-950/40 dark:text-red-400';
    }

    public function formatDuration(int $minutes): string
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
        return view('expert-statistics::livewire.agent-monitoring.realtime-status');
    }

    private function loadData(): void
    {
        try {
            $service = app(ExpertStatisticsService::class);
            $result = $service->getAgentStatsRealtimeStatus();
            $this->agentData = $result['data'] ?? [];
            $this->lastUpdated = now()->format('H:i:s');
            $this->errorMessage = null;

            $map = $service->getMap();
            $this->extensionsMap = $map['extensions'] ?? [];
        } catch (\Throwable) {
            $this->errorMessage = __('expert-statistics::pbx.expert_statistics.error_loading');
        }
    }
}
