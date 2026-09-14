<?php

namespace CXEngine\ExpertStatistics\Livewire;

use CXEngine\ExpertStatistics\Concerns\AuthorizesExpertStatisticsAccess;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;
use Livewire\Component;

/**
 * Free/ungated landing page for the Expert Statistics section, ported from
 * bluerocktelclients' App\Filament\Pages\ExpertStatisticsHome (feature list
 * only — the tutorial-video tabs there are gated on an internal/demo tenant
 * code and have no equivalent content here, so they were left out).
 *
 * Only AuthorizesExpertStatisticsAccess applies (same permission gate as
 * every other page here) — deliberately no RequiresExpertStatisticsActivation,
 * same as the Dashboard: this page is the entry point, it must stay reachable
 * even for a host that isn't activated yet.
 */
class Home extends Component
{
    use AuthorizesExpertStatisticsAccess;

    /**
     * @return array<int, array{name: string, description: string, icon: string, url: string}>
     */
    public function getFeatures(): array
    {
        return array_map(
            fn (array $feature): array => [...$feature, 'url' => $this->urlFor($feature['route'])],
            [
                [
                    'name' => __('expert-statistics::pbx.expert_statistics.nav_dashboard'),
                    'description' => __('expert-statistics::pbx.expert_statistics.home_feature_dashboard_desc'),
                    'icon' => 'heroicon-o-chart-pie',
                    'route' => 'expert-stats.dashboard',
                ],
                [
                    'name' => __('expert-statistics::pbx.expert_statistics.nav_group_my_queues'),
                    'description' => __('expert-statistics::pbx.expert_statistics.home_feature_my_queues_desc'),
                    'icon' => 'heroicon-o-queue-list',
                    'route' => 'expert-stats.my-queues.report',
                ],
                [
                    'name' => __('expert-statistics::pbx.expert_statistics.nav_group_my_users'),
                    'description' => __('expert-statistics::pbx.expert_statistics.home_feature_my_users_desc'),
                    'icon' => 'heroicon-o-users',
                    'route' => 'expert-stats.my-users.report',
                ],
                [
                    'name' => __('expert-statistics::pbx.expert_statistics.nav_group_my_numbers'),
                    'description' => __('expert-statistics::pbx.expert_statistics.home_feature_my_numbers_desc'),
                    'icon' => 'heroicon-o-phone-arrow-down-left',
                    'route' => 'expert-stats.my-numbers.report',
                ],
                [
                    'name' => __('expert-statistics::pbx.expert_statistics.nav_group_caller_numbers'),
                    'description' => __('expert-statistics::pbx.expert_statistics.home_feature_caller_numbers_desc'),
                    'icon' => 'heroicon-o-user-group',
                    'route' => 'expert-stats.caller-numbers.report',
                ],
                [
                    'name' => __('expert-statistics::pbx.expert_statistics.nav_call_analysis'),
                    'description' => __('expert-statistics::pbx.expert_statistics.home_feature_call_details_desc'),
                    'icon' => 'heroicon-o-share',
                    'route' => 'expert-stats.call-details.index',
                ],
                [
                    'name' => __('expert-statistics::pbx.expert_statistics.nav_group_agent_monitoring'),
                    'description' => __('expert-statistics::pbx.expert_statistics.home_feature_agent_monitoring_desc'),
                    'icon' => 'heroicon-o-signal',
                    'route' => 'expert-stats.agent-monitoring.realtime-status',
                ],
                [
                    'name' => __('expert-statistics::pbx.expert_statistics.nav_ai_insights'),
                    'description' => __('expert-statistics::pbx.expert_statistics.home_feature_ai_desc'),
                    'icon' => 'heroicon-o-sparkles',
                    'route' => 'expert-stats.ai.chat',
                ],
            ],
        );
    }

    public function render(): View
    {
        return view('expert-statistics::livewire.home');
    }

    private function urlFor(string $routeName): string
    {
        return Route::has($routeName) ? route($routeName) : '#';
    }
}
