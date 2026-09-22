<?php

// DocsCatalog is a framework-free, pure-static class, so these tests run as
// plain Pest tests with no TestCase/Testbench bootstrap - same rationale as
// PbxDataProcessorTest.

use CXEngine\ExpertStatistics\Support\DocsCatalog;

it('references a Markdown file that actually exists on disk, in every supported locale, for every section', function () {
    foreach (DocsCatalog::LOCALES as $locale) {
        foreach (DocsCatalog::sections() as $section) {
            $path = __DIR__.'/../../../resources/docs/user/'.$locale.'/'.$section['file'];

            expect(is_file($path))->toBeTrue("Missing [{$locale}] doc file for section '{$section['id']}': {$section['file']}")
                ->and(trim((string) file_get_contents($path)))->not->toBe('');
        }
    }
});

it('has unique section ids across the whole catalog', function () {
    $ids = array_column(DocsCatalog::sections(), 'id');

    expect($ids)->toBe(array_unique($ids));
});

it('finds a section by id', function () {
    $section = DocsCatalog::find('dashboard');

    expect($section)->not->toBeNull()
        ->and($section['file'])->toBe('03-dashboard.md');
});

it('returns null for an unknown section id', function () {
    expect(DocsCatalog::find('does-not-exist'))->toBeNull();
});

it('maps a known route name to its relevant doc section', function () {
    expect(DocsCatalog::sectionIdForRoute('expert-stats.dashboard'))->toBe('dashboard')
        ->and(DocsCatalog::sectionIdForRoute('expert-stats.my-queues.kpi'))->toBe('my-queues')
        ->and(DocsCatalog::sectionIdForRoute('expert-stats.ai.alerts'))->toBe('ai-insights');
});

it('returns null when a route has no mapped doc section', function () {
    expect(DocsCatalog::sectionIdForRoute('some.unmapped.route'))->toBeNull()
        ->and(DocsCatalog::sectionIdForRoute(null))->toBeNull();
});

it('every section belongs to exactly one group', function () {
    $sectionsFromGroups = array_merge(...array_column(DocsCatalog::catalog(), 'sections'));

    expect(count($sectionsFromGroups))->toBe(count(DocsCatalog::sections()));
});

it('falls back to English for an unsupported locale', function () {
    expect(DocsCatalog::resolveLocale('de'))->toBe('en')
        ->and(DocsCatalog::resolveLocale(null))->toBe('en')
        ->and(DocsCatalog::resolveLocale('fr'))->toBe('fr')
        ->and(DocsCatalog::resolveLocale('en'))->toBe('en');
});

it('has a translation label, in every supported locale, for every group and section', function () {
    foreach (DocsCatalog::LOCALES as $locale) {
        $lang = require __DIR__."/../../../resources/lang/{$locale}/pbx.php";

        foreach (DocsCatalog::catalog() as $group) {
            expect($lang['docs']['groups'][$group['group']] ?? null)
                ->not->toBeNull("Missing [{$locale}] docs.groups.{$group['group']} translation");

            foreach ($group['sections'] as $section) {
                expect($lang['docs']['sections'][$section['id']] ?? null)
                    ->not->toBeNull("Missing [{$locale}] docs.sections.{$section['id']} translation");
            }
        }
    }
});
