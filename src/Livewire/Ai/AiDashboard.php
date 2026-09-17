<?php

namespace CXEngine\ExpertStatistics\Livewire\Ai;

use CXEngine\ExpertStatistics\Concerns\AuthorizesExpertStatisticsAccess;
use CXEngine\ExpertStatistics\Concerns\RequiresExpertStatisticsActivation;
use CXEngine\ExpertStatistics\Services\ExpertStatisticsService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * AI-generated insights feed, ported from bluerocktelclients'
 * App\Filament\Pages\Ai\AiDashboardPage.
 */
class AiDashboard extends Component
{
    use AuthorizesExpertStatisticsAccess;
    use RequiresExpertStatisticsActivation;

    /** @var array<int, array<string, mixed>> */
    public array $panels = [];

    /** @var array<string, mixed> */
    public array $meta = [];

    public ?string $errorMessage = null;

    public function mount(): void
    {
        $this->loadData();
    }

    public function refresh(): void
    {
        try {
            $result = app(ExpertStatisticsService::class)->refreshAiDashboard();
            $this->panels = $result['panels'] ?? [];
            $this->meta = $result['meta'] ?? [];
            $this->errorMessage = null;
        } catch (\Throwable) {
            $this->errorMessage = __('expert-statistics::pbx.expert_statistics.error_loading');
        }
    }

    public function readPanel(string $panelId): void
    {
        try {
            app(ExpertStatisticsService::class)->readAiInsight($panelId);
        } catch (\Throwable) {
            return;
        }

        $this->panels = array_map(
            fn (array $p): array => (string) ($p['id'] ?? '') === $panelId ? [...$p, 'is_read' => true] : $p,
            $this->panels
        );

        if (! empty($this->meta) && ! empty($this->meta['unread_count'])) {
            $this->meta['unread_count'] = max(0, $this->meta['unread_count'] - 1);
        }
    }

    public function dismissPanel(string $panelId): void
    {
        try {
            app(ExpertStatisticsService::class)->dismissAiInsight($panelId);
        } catch (\Throwable) {
            return;
        }

        $this->panels = array_values(
            array_filter($this->panels, fn (array $p): bool => (string) ($p['id'] ?? '') !== $panelId)
        );

        if (! empty($this->meta)) {
            $this->meta['total_panels'] = max(0, ($this->meta['total_panels'] ?? 0) - 1);
        }
    }

    public function markAllRead(): void
    {
        try {
            app(ExpertStatisticsService::class)->readAllAiInsights();
        } catch (\Throwable) {
            return;
        }

        $this->panels = array_map(fn (array $p): array => [...$p, 'is_read' => true], $this->panels);

        if (! empty($this->meta)) {
            $this->meta['unread_count'] = 0;
        }
    }

    public function getPanelSeverityClass(string $severity): string
    {
        return match ($severity) {
            'critical' => 'bg-red-100 text-red-700 dark:bg-red-950/40 dark:text-red-400',
            'warning' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-950/40 dark:text-yellow-400',
            default => 'bg-blue-100 text-blue-700 dark:bg-blue-950/40 dark:text-blue-400',
        };
    }

    public function getPanelBorderClass(string $severity): string
    {
        return match ($severity) {
            'critical' => 'border-l-4 border-red-500',
            'warning' => 'border-l-4 border-yellow-500',
            default => 'border-l-4 border-blue-400',
        };
    }

    public function getPanelTypeLabel(string $type): string
    {
        $label = __('expert-statistics::pbx.expert_statistics.ai_dashboard_type_'.$type);

        return $label !== 'expert-statistics::pbx.expert_statistics.ai_dashboard_type_'.$type
            ? $label
            : ucfirst(str_replace('_', ' ', $type));
    }

    public function getTypeIconBgClass(string $type): string
    {
        return match ($type) {
            'alert_summary' => 'bg-red-100 text-red-600 dark:bg-red-950/40 dark:text-red-400',
            'trend', 'kpi_snapshot' => 'bg-teal-100 text-teal-600 dark:bg-teal-950/40 dark:text-teal-400',
            'pattern' => 'bg-orange-100 text-orange-600 dark:bg-orange-950/40 dark:text-orange-400',
            'recommendation' => 'bg-blue-100 text-blue-600 dark:bg-blue-950/40 dark:text-blue-400',
            'suggestion' => 'bg-violet-100 text-violet-600 dark:bg-violet-950/40 dark:text-violet-400',
            'quick_action' => 'bg-purple-100 text-purple-600 dark:bg-purple-950/40 dark:text-purple-400',
            default => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
        };
    }

    public function getTypeBadgeClass(string $type): string
    {
        return match ($type) {
            'alert_summary' => 'bg-red-50 text-red-600 border border-red-200 dark:bg-red-950/30 dark:text-red-400 dark:border-red-800',
            'trend', 'kpi_snapshot' => 'bg-teal-50 text-teal-700 border border-teal-200 dark:bg-teal-950/30 dark:text-teal-400 dark:border-teal-800',
            'pattern' => 'bg-orange-50 text-orange-700 border border-orange-200 dark:bg-orange-950/30 dark:text-orange-400 dark:border-orange-800',
            'recommendation' => 'bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-950/30 dark:text-blue-400 dark:border-blue-800',
            'suggestion' => 'bg-violet-50 text-violet-700 border border-violet-200 dark:bg-violet-950/30 dark:text-violet-400 dark:border-violet-800',
            'quick_action' => 'bg-purple-50 text-purple-700 border border-purple-200 dark:bg-purple-950/30 dark:text-purple-400 dark:border-purple-800',
            default => 'bg-gray-100 text-gray-600 border border-gray-200 dark:bg-gray-700 dark:text-gray-400 dark:border-gray-600',
        };
    }

    public function getPanelColSpan(string $type): string
    {
        return match ($type) {
            'alert_summary', 'quick_action' => 'lg:col-span-3',
            'recommendation' => 'lg:col-span-2',
            default => 'lg:col-span-1',
        };
    }

    public function render(): View
    {
        return view('expert-statistics::livewire.ai.dashboard');
    }

    private function loadData(): void
    {
        try {
            $result = app(ExpertStatisticsService::class)->getAiDashboard();
            $this->panels = $result['panels'] ?? [];
            $this->meta = $result['meta'] ?? [];
            $this->errorMessage = null;
        } catch (\Throwable) {
            $this->errorMessage = __('expert-statistics::pbx.expert_statistics.error_loading');
        }
    }
}
