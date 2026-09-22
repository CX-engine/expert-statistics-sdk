<?php

declare(strict_types=1);

namespace CXEngine\ExpertStatistics\Support;

/**
 * Static manifest for the end-user documentation shipped in
 * resources/docs/user/*.md, rendered by the Docs Helper panel
 * (Livewire\Docs\DocsHelperPanel). Keeping this as a plain array (rather
 * than parsing Markdown headings at runtime) means the table of contents,
 * grouping, and per-route "which section is relevant here" mapping are all
 * explicit and don't depend on Markdown structure staying stable.
 */
final class DocsCatalog
{
    /**
     * @return array<int, array{group: string, sections: array<int, array{id: string, title: string, file: string, routes: array<int, string>}>}>
     */
    public static function catalog(): array
    {
        return [
            [
                'group' => 'Start here',
                'sections' => [
                    ['id' => 'getting-started', 'title' => 'Getting Started', 'file' => '01-getting-started.md', 'routes' => ['expert-stats.home']],
                    ['id' => 'permissions', 'title' => 'Permissions & Access', 'file' => '02-permissions.md', 'routes' => []],
                ],
            ],
            [
                'group' => 'Dashboards & reports',
                'sections' => [
                    ['id' => 'dashboard', 'title' => 'Dashboard', 'file' => '03-dashboard.md', 'routes' => ['expert-stats.dashboard']],
                    ['id' => 'my-queues', 'title' => 'My Queues', 'file' => '04-my-queues.md', 'routes' => [
                        'expert-stats.my-queues.report', 'expert-stats.my-queues.dashboard',
                        'expert-stats.my-queues.kpi', 'expert-stats.my-queues.origins',
                    ]],
                    ['id' => 'my-users', 'title' => 'My Users', 'file' => '05-my-users.md', 'routes' => [
                        'expert-stats.my-users.report', 'expert-stats.my-users.dashboard',
                        'expert-stats.my-users.kpi', 'expert-stats.my-users.origins',
                    ]],
                    ['id' => 'numbers', 'title' => 'My Numbers & Caller Numbers', 'file' => '06-my-numbers-caller-numbers.md', 'routes' => [
                        'expert-stats.my-numbers.report', 'expert-stats.caller-numbers.report',
                    ]],
                    ['id' => 'call-analysis', 'title' => 'Call Analyser', 'file' => '07-call-analysis.md', 'routes' => ['expert-stats.call-details.index']],
                    ['id' => 'agent-monitoring', 'title' => 'Agent Monitoring', 'file' => '08-agent-monitoring.md', 'routes' => [
                        'expert-stats.agent-monitoring.realtime-status', 'expert-stats.agent-monitoring.queue-connection',
                        'expert-stats.agent-monitoring.status-breakdown',
                    ]],
                    ['id' => 'ai-insights', 'title' => 'AI Insights', 'file' => '09-ai-insights.md', 'routes' => [
                        'expert-stats.ai.chat', 'expert-stats.ai.dashboard', 'expert-stats.ai.alerts',
                    ]],
                ],
            ],
            [
                'group' => 'Configuration',
                'sections' => [
                    ['id' => 'pbx-settings', 'title' => 'PBX Settings', 'file' => '10-pbx-settings.md', 'routes' => ['expert-stats.configuration.settings']],
                    ['id' => 'scheduled-reports', 'title' => 'Scheduled Reports & Sharing', 'file' => '11-scheduled-reports-and-sharing.md', 'routes' => ['expert-stats.reports.scheduled']],
                    ['id' => 'wallboard', 'title' => 'Wallboard & Live Data', 'file' => '12-wallboard-live-data.md', 'routes' => []],
                ],
            ],
            [
                'group' => 'Reference',
                'sections' => [
                    ['id' => 'glossary', 'title' => 'KPI & Report Glossary', 'file' => '13-kpi-and-report-glossary.md', 'routes' => []],
                    ['id' => 'faq', 'title' => 'FAQ & Troubleshooting', 'file' => '14-faq-troubleshooting.md', 'routes' => []],
                ],
            ],
        ];
    }

    /**
     * @return array<int, array{id: string, title: string, file: string, routes: array<int, string>}>
     */
    public static function sections(): array
    {
        return array_merge(...array_column(self::catalog(), 'sections'));
    }

    public static function find(string $id): ?array
    {
        foreach (self::sections() as $section) {
            if ($section['id'] === $id) {
                return $section;
            }
        }

        return null;
    }

    /**
     * Maps the current Livewire route name to the doc section most relevant
     * to what's on screen, so the help panel can open directly to it.
     */
    public static function sectionIdForRoute(?string $routeName): ?string
    {
        if ($routeName === null) {
            return null;
        }

        foreach (self::sections() as $section) {
            if (in_array($routeName, $section['routes'], true)) {
                return $section['id'];
            }
        }

        return null;
    }
}
