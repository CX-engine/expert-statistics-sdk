<?php

// Regression coverage for a real bug: HeadingPermalinkExtension defaults to
// prefixing every heading id with "content-" (e.g. "content-onglet-agent"),
// silently breaking every #anchor authored in the Markdown source (which are
// written as plain heading slugs). DocsHelperPanel::renderMarkdown() sets
// 'id_prefix' => '' to avoid this - this test renders the docs through the
// exact same CommonMark config and confirms every internal #anchor link
// actually resolves to a real heading id, for every supported locale. Plain
// Pest test, no Testbench bootstrap, same rationale as PbxDataProcessorTest.

use CXEngine\ExpertStatistics\Support\DocsCatalog;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\MarkdownConverter;

function docsConverter(): MarkdownConverter
{
    $environment = new Environment([
        'html_input' => 'strip',
        'allow_unsafe_links' => false,
        'heading_permalink' => [
            'apply_id_to_heading' => true,
            'insert' => 'none',
            'id_prefix' => '',
        ],
    ]);
    $environment->addExtension(new CommonMarkCoreExtension);
    $environment->addExtension(new HeadingPermalinkExtension);

    return new MarkdownConverter($environment);
}

it('never generates a "content-" prefixed heading id (the historical default-config bug)', function () {
    $html = (string) docsConverter()->convert("## Some Heading\n\nText.");

    expect($html)->toContain('id="some-heading"')
        ->not->toContain('id="content-');
});

it('resolves every internal #anchor link to a real heading id, for every locale', function () {
    $converter = docsConverter();

    foreach (DocsCatalog::LOCALES as $locale) {
        $dir = __DIR__."/../../../resources/docs/user/{$locale}";

        $headingIdsByFile = [];
        foreach (glob($dir.'/*.md') as $path) {
            $html = (string) $converter->convert((string) file_get_contents($path));
            preg_match_all('/<h[1-6][^>]*\sid="([^"]+)"/', $html, $matches);
            $headingIdsByFile[basename($path)] = $matches[1];
        }

        foreach (glob($dir.'/*.md') as $path) {
            $markdown = (string) file_get_contents($path);
            preg_match_all('/\]\(([0-9]{2}-[a-z-]+\.md)#([^)]+)\)/', $markdown, $links, PREG_SET_ORDER);

            foreach ($links as [$_, $targetFile, $anchor]) {
                $exists = in_array($anchor, $headingIdsByFile[$targetFile] ?? [], true);

                expect($exists)->toBeTrue(
                    "[{$locale}] ".basename($path)." links to {$targetFile}#{$anchor}, but that heading id doesn't exist."
                );
            }
        }
    }
});
