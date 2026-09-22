@props(['title' => null, 'subtitle' => null])

{{--
    Shared page shell for every Expert Statistics page, matching Filament's
    cluster sub-navigation pattern (a secondary nav listing the cluster's
    own pages, rendered alongside the page content - see
    https://filamentphp.com/docs/navigation/clusters, "start" position).
--}}
<x-pages.index :title="$title" :subtitle="$subtitle">
    <div class="lg:flex lg:items-start lg:gap-8">
        <nav class="mb-6 lg:mb-0 lg:w-56 lg:shrink-0">
            <ul class="space-y-1">
                <x-menus.item
                    :title="__('expert-statistics::pbx.expert_statistics.home_nav_label')"
                    route="expert-stats.home"
                    icon="phosphor-house"
                />
                <x-menus.item
                    :title="__('expert-statistics::pbx.expert_statistics.nav_dashboard')"
                    path="/expert-stats/dashboard"
                    icon="phosphor-chart-line"
                />
                <x-menus.item
                    :title="__('expert-statistics::pbx.expert_statistics.nav_group_my_numbers')"
                    route="expert-stats.my-numbers.report"
                    icon="phosphor-phone-incoming"
                />
                <x-menus.item
                    :title="__('expert-statistics::pbx.expert_statistics.nav_group_caller_numbers')"
                    route="expert-stats.caller-numbers.report"
                    icon="phosphor-user-list"
                />

                <x-menus.item-dropdown
                    :title="__('expert-statistics::pbx.expert_statistics.nav_group_my_queues')"
                    icon="phosphor-queue"
                    openPath="/expert-stats/my-queues/*"
                >
                    <x-menus.item :title="__('expert-statistics::pbx.expert_statistics.nav_dashboard')" route="expert-stats.my-queues.dashboard" class="pl-10" />
                    <x-menus.item :title="__('expert-statistics::pbx.expert_statistics.nav_report')" route="expert-stats.my-queues.report" class="pl-10" />
                    <x-menus.item :title="__('expert-statistics::pbx.expert_statistics.nav_kpi')" route="expert-stats.my-queues.kpi" class="pl-10" />
                    <x-menus.item :title="__('expert-statistics::pbx.expert_statistics.nav_origins')" route="expert-stats.my-queues.origins" class="pl-10" />
                </x-menus.item-dropdown>

                <x-menus.item-dropdown
                    :title="__('expert-statistics::pbx.expert_statistics.nav_group_my_users')"
                    icon="phosphor-users-three"
                    openPath="/expert-stats/my-users/*"
                >
                    <x-menus.item :title="__('expert-statistics::pbx.expert_statistics.nav_dashboard')" route="expert-stats.my-users.dashboard" class="pl-10" />
                    <x-menus.item :title="__('expert-statistics::pbx.expert_statistics.nav_report')" route="expert-stats.my-users.report" class="pl-10" />
                    <x-menus.item :title="__('expert-statistics::pbx.expert_statistics.nav_kpi')" route="expert-stats.my-users.kpi" class="pl-10" />
                    <x-menus.item :title="__('expert-statistics::pbx.expert_statistics.nav_origins')" route="expert-stats.my-users.origins" class="pl-10" />
                </x-menus.item-dropdown>

                <x-menus.item-dropdown
                    :title="__('expert-statistics::pbx.expert_statistics.nav_group_agent_monitoring_short')"
                    icon="phosphor-headset"
                    openPath="/expert-stats/agent-monitoring/*"
                >
                    <x-menus.item :title="__('expert-statistics::pbx.expert_statistics.nav_realtime_status')" route="expert-stats.agent-monitoring.realtime-status" class="pl-10" />
                    <x-menus.item :title="__('expert-statistics::pbx.expert_statistics.nav_agent_monitoring_queue_connection')" route="expert-stats.agent-monitoring.queue-connection" class="pl-10" />
                    <x-menus.item :title="__('expert-statistics::pbx.expert_statistics.nav_agent_monitoring_status_breakdown')" route="expert-stats.agent-monitoring.status-breakdown" class="pl-10" />
                </x-menus.item-dropdown>

                <x-menus.item
                    :title="__('expert-statistics::pbx.expert_statistics.nav_call_analysis')"
                    route="expert-stats.call-details.index"
                    icon="phosphor-share-network"
                />

                <x-menus.item-dropdown
                    :title="__('expert-statistics::pbx.expert_statistics.nav_ai_insights')"
                    icon="phosphor-sparkle"
                    openPath="/expert-stats/ai/*"
                >
                    <x-menus.item :title="__('expert-statistics::pbx.expert_statistics.nav_ai_chat')" route="expert-stats.ai.chat" class="pl-10" />
                    <x-menus.item :title="__('expert-statistics::pbx.expert_statistics.nav_ai_dashboard')" route="expert-stats.ai.dashboard" class="pl-10" />
                    <x-menus.item :title="__('expert-statistics::pbx.expert_statistics.nav_ai_alerts')" route="expert-stats.ai.alerts" class="pl-10" />
                </x-menus.item-dropdown>
            </ul>
        </nav>

        <div class="min-w-0 flex-1">
            {{ $slot }}
        </div>
    </div>

    @if (config('expert-statistics-api.docs_panel_enabled', true))
        <livewire:expert-statistics.docs.helper-panel />
    @endif
</x-pages.index>
