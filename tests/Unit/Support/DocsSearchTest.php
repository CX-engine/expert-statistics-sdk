<?php

// DocsSearch is a framework-free, pure-static class (like DocsCatalog), so
// these tests run as plain Pest tests with no TestCase/Testbench bootstrap.

use CXEngine\ExpertStatistics\Support\DocsSearch;

it('ranks a relevant section first', function () {
    $ids = DocsSearch::topSectionIds('how do I create a resource group', 'en', 3);

    // Both pages genuinely discuss resource groups (PBX Settings' Groups
    // tab creates them, Scheduled Reports uses them) - either is a
    // reasonable top match, unlike some unrelated section winning.
    expect($ids)->not->toBeEmpty()
        ->and($ids[0])->toBeIn(['pbx-settings', 'scheduled-reports']);
});

it('finds the wallboard section for a wallboard question', function () {
    $ids = DocsSearch::topSectionIds('how to build a wallboard for the office TV', 'en', 3);

    expect($ids)->toContain('wallboard');
});

it('respects the limit', function () {
    $ids = DocsSearch::topSectionIds('report queue user dashboard call', 'en', 2);

    expect($ids)->toHaveCount(2);
});

it('returns an empty array for a query with only stopwords', function () {
    expect(DocsSearch::topSectionIds('how do I', 'en', 3))->toBe([]);
});

it('returns an empty array for an empty query', function () {
    expect(DocsSearch::topSectionIds('', 'en', 3))->toBe([]);
});

it('works the same way for French queries', function () {
    $ids = DocsSearch::topSectionIds('comment créer un groupe de ressources', 'fr', 3);

    expect($ids)->not->toBeEmpty()
        ->and($ids[0])->toBe('pbx-settings');
});

it('still finds matches for an unsupported locale, via DocsContent\'s English fallback', function () {
    // DocsContent::raw() (which DocsSearch reads through) resolves any
    // unsupported locale to English internally - so scoring still works,
    // it's just always matching against the English text in that case.
    $ids = DocsSearch::topSectionIds('resource group', 'de', 3);

    expect($ids)->not->toBeEmpty();
});
