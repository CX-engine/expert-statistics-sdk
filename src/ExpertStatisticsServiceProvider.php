<?php

namespace CXEngine\ExpertStatistics;

use CXEngine\ExpertStatistics\Livewire\AgentMonitoring\QueueConnection;
use CXEngine\ExpertStatistics\Livewire\AgentMonitoring\RealtimeStatus;
use CXEngine\ExpertStatistics\Livewire\AgentMonitoring\StatusBreakdown;
use CXEngine\ExpertStatistics\Livewire\Ai\AiAlerts;
use CXEngine\ExpertStatistics\Livewire\Ai\AiChat;
use CXEngine\ExpertStatistics\Livewire\Ai\AiDashboard;
use CXEngine\ExpertStatistics\Livewire\Ai\AiFloatingChat;
use CXEngine\ExpertStatistics\Livewire\CallAnalysis\CallAnalysis;
use CXEngine\ExpertStatistics\Livewire\Configuration\ManagePbxSettings;
use CXEngine\ExpertStatistics\Livewire\Dashboard;
use CXEngine\ExpertStatistics\Livewire\Docs\DocsAssistantChat;
use CXEngine\ExpertStatistics\Livewire\Docs\DocsHelperPanel;
use CXEngine\ExpertStatistics\Livewire\Home;
use CXEngine\ExpertStatistics\Livewire\Reports\CallerNumbers\CallerNumbersReport;
use CXEngine\ExpertStatistics\Livewire\Reports\ManageScheduledReports;
use CXEngine\ExpertStatistics\Livewire\Reports\MyNumbers\MyNumbersReport;
use CXEngine\ExpertStatistics\Livewire\Reports\MyQueues\MyQueuesDashboard;
use CXEngine\ExpertStatistics\Livewire\Reports\MyQueues\MyQueuesKpi;
use CXEngine\ExpertStatistics\Livewire\Reports\MyQueues\MyQueuesOrigins;
use CXEngine\ExpertStatistics\Livewire\Reports\MyQueues\MyQueuesReport;
use CXEngine\ExpertStatistics\Livewire\Reports\MyUsers\MyUsersDashboard;
use CXEngine\ExpertStatistics\Livewire\Reports\MyUsers\MyUsersKpi;
use CXEngine\ExpertStatistics\Livewire\Reports\MyUsers\MyUsersOrigins;
use CXEngine\ExpertStatistics\Livewire\Reports\MyUsers\MyUsersReport;
use CXEngine\ExpertStatistics\Livewire\Reports\ShareReportModal;
use CXEngine\ExpertStatistics\Livewire\Wallboard\PublicWallboard;
use CXEngine\ExpertStatistics\Services\ExpertStatisticsService;
use CXEngine\ExpertStatistics\Services\PrismDocsAssistantResponder;
use CXEngine\ExpertStats\ExpertStatisticsConnector;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class ExpertStatisticsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/expert-statistics-api.php', 'expert-statistics-api');

        $this->app->singleton(ExpertStatisticsConnector::class, function (): ExpertStatisticsConnector {
            return new ExpertStatisticsConnector(
                apiUrl: (string) config('expert-statistics-api.api_url'),
                email: (string) config('expert-statistics-api.email'),
                password: (string) config('expert-statistics-api.password'),
            );
        });

        $this->app->singleton(ExpertStatisticsService::class, function ($app): ExpertStatisticsService {
            return new ExpertStatisticsService(
                connector: $app->make(ExpertStatisticsConnector::class),
                hostResolver: $app->make(Contracts\ResolvesActivePbxHost::class),
                defaultTtl: (int) config('expert-statistics-api.cache_ttl', 300),
            );
        });

        // Deliberately NOT given an ExpertStatisticsService - see that
        // class's own docblock for why the docs assistant must never be
        // able to reach call data.
        $this->app->bind(Contracts\AnswersDocsQuestions::class, PrismDocsAssistantResponder::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'expert-statistics');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'expert-statistics');

        $this->publishes([
            __DIR__.'/../config/expert-statistics-api.php' => config_path('expert-statistics-api.php'),
        ], 'expert-statistics-config');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/expert-statistics'),
        ], 'expert-statistics-views');

        $this->publishes([
            __DIR__.'/../resources/lang' => lang_path('vendor/expert-statistics'),
        ], 'expert-statistics-lang');

        if (class_exists(Livewire::class)) {
            Livewire::component('expert-statistics.home', Home::class);

            Livewire::component('expert-statistics.dashboard', Dashboard::class);

            Livewire::component('expert-statistics.my-queues.report', MyQueuesReport::class);
            Livewire::component('expert-statistics.my-queues.dashboard', MyQueuesDashboard::class);
            Livewire::component('expert-statistics.my-queues.kpi', MyQueuesKpi::class);
            Livewire::component('expert-statistics.my-queues.origins', MyQueuesOrigins::class);

            Livewire::component('expert-statistics.my-users.report', MyUsersReport::class);
            Livewire::component('expert-statistics.my-users.dashboard', MyUsersDashboard::class);
            Livewire::component('expert-statistics.my-users.kpi', MyUsersKpi::class);
            Livewire::component('expert-statistics.my-users.origins', MyUsersOrigins::class);

            Livewire::component('expert-statistics.my-numbers.report', MyNumbersReport::class);
            Livewire::component('expert-statistics.caller-numbers.report', CallerNumbersReport::class);

            Livewire::component('expert-statistics.configuration.manage-pbx-settings', ManagePbxSettings::class);

            Livewire::component('expert-statistics.reports.share-report-modal', ShareReportModal::class);
            Livewire::component('expert-statistics.reports.manage-scheduled-reports', ManageScheduledReports::class);

            Livewire::component('expert-statistics.call-analysis.index', CallAnalysis::class);

            Livewire::component('expert-statistics.wallboard.public', PublicWallboard::class);

            Livewire::component('expert-statistics.agent-monitoring.realtime-status', RealtimeStatus::class);
            Livewire::component('expert-statistics.agent-monitoring.queue-connection', QueueConnection::class);
            Livewire::component('expert-statistics.agent-monitoring.status-breakdown', StatusBreakdown::class);

            Livewire::component('expert-statistics.ai.chat', AiChat::class);
            Livewire::component('expert-statistics.ai.dashboard', AiDashboard::class);
            Livewire::component('expert-statistics.ai.alerts', AiAlerts::class);
            Livewire::component('expert-statistics.ai.floating-chat', AiFloatingChat::class);

            Livewire::component('expert-statistics.docs.helper-panel', DocsHelperPanel::class);
            Livewire::component('expert-statistics.docs.assistant-chat', DocsAssistantChat::class);
        }
    }
}
