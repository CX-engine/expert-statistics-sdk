<?php

declare(strict_types=1);

namespace CXEngine\ExpertStatistics\Support;

/**
 * Locale-aware raw Markdown reader for the doc set catalogued by DocsCatalog.
 * Shared by DocsHelperPanel (renders it) and the docs assistant's retriever/
 * responder (feeds it to the LLM as grounding context) so both read exactly
 * the same source of truth, with the same English fallback behaviour.
 */
final class DocsContent
{
    public static function raw(string $sectionId, ?string $locale): string
    {
        $section = DocsCatalog::find($sectionId);

        if ($section === null) {
            return '';
        }

        $resolvedLocale = DocsCatalog::resolveLocale($locale);
        $path = self::path($section['file'], $resolvedLocale);

        if (! is_file($path) && $resolvedLocale !== DocsCatalog::DEFAULT_LOCALE) {
            $path = self::path($section['file'], DocsCatalog::DEFAULT_LOCALE);
        }

        return is_file($path) ? (string) file_get_contents($path) : '';
    }

    private static function path(string $file, string $locale): string
    {
        return __DIR__.'/../../resources/docs/user/'.$locale.'/'.$file;
    }
}
