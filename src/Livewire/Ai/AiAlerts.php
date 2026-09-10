<?php

namespace CXEngine\ExpertStatistics\Livewire\Ai;

use CXEngine\ExpertStatistics\Concerns\AuthorizesExpertStatisticsAccess;
use CXEngine\ExpertStatistics\Services\ExpertStatisticsService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Triggered AI-alerts list, ported from bluerocktelclients'
 * App\Filament\Pages\Ai\AiAlertsPage. Distinct from the AI-alert
 * *settings* form (Configuration ▸ AI Alerts tab, added in an earlier
 * phase) — this page shows and manages alerts already raised by the
 * backend's alert checker.
 */
class AiAlerts extends Component
{
    use AuthorizesExpertStatisticsAccess;

    /** @var array<int, array<string, mixed>> */
    public array $alerts = [];

    /** @var array<string, int> */
    public array $summary = [];

    public bool $isChecking = false;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        $this->loadAlerts();
    }

    public function checkNow(): void
    {
        $this->isChecking = true;

        try {
            app(ExpertStatisticsService::class)->checkAiAlertsNow();
            $this->loadAlerts();
        } catch (\Throwable) {
            $this->errorMessage = __('expert-statistics::pbx.expert_statistics.error_loading');
        } finally {
            $this->isChecking = false;
        }
    }

    public function dismissAlert(int $alertId): void
    {
        try {
            app(ExpertStatisticsService::class)->dismissAiAlert($alertId);
        } catch (\Throwable) {
            return;
        }

        $this->alerts = array_values(
            array_filter($this->alerts, fn (array $a): bool => (int) ($a['id'] ?? 0) !== $alertId)
        );

        if (! empty($this->summary)) {
            $this->summary['total'] = max(0, ($this->summary['total'] ?? 0) - 1);
        }
    }

    public function getSeverityBadgeClass(string $severity): string
    {
        return match ($severity) {
            'critical' => 'bg-red-100 text-red-700 dark:bg-red-950/40 dark:text-red-400',
            'warning' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-950/40 dark:text-yellow-400',
            default => 'bg-blue-100 text-blue-700 dark:bg-blue-950/40 dark:text-blue-400',
        };
    }

    public function getCategoryIconClass(string $category): string
    {
        return match ($category) {
            'queue_performance' => 'bg-violet-100 text-violet-600 dark:bg-violet-950/40 dark:text-violet-400',
            'peak_hours' => 'bg-orange-100 text-orange-600 dark:bg-orange-950/40 dark:text-orange-400',
            'volume' => 'bg-teal-100 text-teal-600 dark:bg-teal-950/40 dark:text-teal-400',
            'agent_performance' => 'bg-emerald-100 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400',
            default => 'bg-blue-100 text-blue-600 dark:bg-blue-950/40 dark:text-blue-400',
        };
    }

    public function render(): View
    {
        return view('expert-statistics::livewire.ai.alerts');
    }

    private function loadAlerts(): void
    {
        try {
            $result = app(ExpertStatisticsService::class)->getAiAlerts();
            $this->alerts = $result['alerts'] ?? [];
            $this->summary = $result['summary'] ?? [];
            $this->errorMessage = null;
        } catch (\Throwable) {
            $this->errorMessage = __('expert-statistics::pbx.expert_statistics.error_loading');
            $this->alerts = [];
            $this->summary = [];
        }
    }
}
