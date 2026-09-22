<?php

declare(strict_types=1);

namespace CXEngine\ExpertStatistics\Support;

/**
 * Static manifest for the end-user documentation shipped in
 * resources/docs/user/{locale}/*.md, rendered by the Docs Helper panel
 * (Livewire\Docs\DocsHelperPanel). Keeping this as a plain array (rather
 * than parsing Markdown headings at runtime) means the table of contents,
 * grouping, and per-route "which section is relevant here" mapping are all
 * explicit and don't depend on Markdown structure staying stable.
 *
 * Deliberately framework-free (no __() calls here - this class is exercised
 * by plain Pest tests with no Testbench bootstrap, see DocsCatalogTest).
 * Group and section *labels* are translation keys resolved by the caller
 * via __('expert-statistics::pbx.docs.groups.'.$group) and
 * __('expert-statistics::pbx.docs.sections.'.$id) - see resources/lang/{en,fr}/pbx.php.
 */
final class DocsCatalog
{
    /**
     * Locales with a full translated copy of the documentation. Callers
     * should fall back to the first of these (English) when the app's
     * current locale isn't in this list.
     */
    public const LOCALES = ['en', 'fr'];

    public const DEFAULT_LOCALE = 'en';

    /**
     * @return array<int, array{group: string, sections: array<int, array{id: string, file: string, routes: array<int, string>}>}>
     */
    public static function catalog(): array
    {
        return [
            [
                'group' => 'start_here',
                'sections' => [
                    ['id' => 'getting-started', 'file' => '01-getting-started.md', 'routes' => ['expert-stats.home']],
                    ['id' => 'permissions', 'file' => '02-permissions.md', 'routes' => []],
                ],
            ],
            [
                'group' => 'dashboards_reports',
                'sections' => [
                    ['id' => 'dashboard', 'file' => '03-dashboard.md', 'routes' => ['expert-stats.dashboard']],
                    ['id' => 'my-queues', 'file' => '04-my-queues.md', 'routes' => [
                        'expert-stats.my-queues.report', 'expert-stats.my-queues.dashboard',
                        'expert-stats.my-queues.kpi', 'expert-stats.my-queues.origins',
                    ]],
                    ['id' => 'my-users', 'file' => '05-my-users.md', 'routes' => [
                        'expert-stats.my-users.report', 'expert-stats.my-users.dashboard',
                        'expert-stats.my-users.kpi', 'expert-stats.my-users.origins',
                    ]],
                    ['id' => 'numbers', 'file' => '06-my-numbers-caller-numbers.md', 'routes' => [
                        'expert-stats.my-numbers.report', 'expert-stats.caller-numbers.report',
                    ]],
                    ['id' => 'call-analysis', 'file' => '07-call-analysis.md', 'routes' => ['expert-stats.call-details.index']],
                    ['id' => 'agent-monitoring', 'file' => '08-agent-monitoring.md', 'routes' => [
                        'expert-stats.agent-monitoring.realtime-status', 'expert-stats.agent-monitoring.queue-connection',
                        'expert-stats.agent-monitoring.status-breakdown',
                    ]],
                    ['id' => 'ai-insights', 'file' => '09-ai-insights.md', 'routes' => [
                        'expert-stats.ai.chat', 'expert-stats.ai.dashboard', 'expert-stats.ai.alerts',
                    ]],
                ],
            ],
            [
                'group' => 'configuration',
                'sections' => [
                    ['id' => 'pbx-settings', 'file' => '10-pbx-settings.md', 'routes' => ['expert-stats.configuration.settings']],
                    ['id' => 'scheduled-reports', 'file' => '11-scheduled-reports-and-sharing.md', 'routes' => ['expert-stats.reports.scheduled']],
                    ['id' => 'wallboard', 'file' => '12-wallboard-live-data.md', 'routes' => []],
                ],
            ],
            [
                'group' => 'reference',
                'sections' => [
                    ['id' => 'glossary', 'file' => '13-kpi-and-report-glossary.md', 'routes' => []],
                    ['id' => 'faq', 'file' => '14-faq-troubleshooting.md', 'routes' => []],
                ],
            ],
        ];
    }

    /**
     * @return array<int, array{id: string, file: string, routes: array<int, string>}>
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

    /**
     * Resolves a requested locale to one this documentation set actually
     * has a translated copy for, falling back to DEFAULT_LOCALE.
     */
    public static function resolveLocale(?string $locale): string
    {
        return in_array($locale, self::LOCALES, true) ? $locale : self::DEFAULT_LOCALE;
    }
}
