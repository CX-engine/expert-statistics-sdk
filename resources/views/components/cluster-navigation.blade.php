{{--
    The cluster's own secondary navigation. Only included when the
    host-provided page shell is enabled (see `page_shell` in
    config/expert-statistics-api.php): it is built from the host's
    <x-menus.*> components, which a host without that shell (e.g. one
    whose pages live in a Filament panel with its own navigation) does
    not have - and Blade resolves component tags when it compiles a view,
    so they must stay out of any view such a host renders.
--}}
<nav class="mb-6 lg:mb-0 lg:w-56 lg:shrink-0">
    <ul class="space-y-1">
        <x-menus.item
            :title="__('expert-statistics::pbx.expert_statistics.home_nav_label')"
            route="expert-stats.home"
            icon="phosphor-house"
        />
        @if (config('expert-statistics-api.dashboard_enabled', true))
            <x-menus.item
                :title="__('expert-statistics::pbx.expert_statistics.nav_dashboard')"
                path="/expert-stats/dashboard"
                icon="phosphor-chart-line"
            />
        @endif
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
            @if (\Illuminate\Support\Facades\Route::has('expert-stats.my-users.outbound'))
                <x-menus.item :title="__('expert-statistics::pbx.expert_statistics.nav_outbound')" route="expert-stats.my-users.outbound" class="pl-10" />
            @endif
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

        @if (\Illuminate\Support\Facades\Route::has('expert-stats.training') && app(\CXEngine\ExpertStatistics\Contracts\ProvidesTrainingParticipants::class)->isTrainingAvailable())
            <x-menus.item
                :title="__('expert-statistics::pbx.training.nav_label')"
                route="expert-stats.training"
                icon="phosphor-graduation-cap"
            />
        @endif
    </ul>
</nav>
