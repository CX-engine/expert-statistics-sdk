<?php

namespace CXEngine\ExpertStatistics\Support;

/**
 * resources/js/backup-reader.js for the AI Audit files tab (the package has no JS
 * build and nothing to publish), loaded once per page with @assets.
 *
 * Given as a base64 data: URL, never inline: on a full page load Livewire 3
 * injects @assets with preg_replace() using them as the replacement string, which
 * collapses "\\" and turns "\1"/"$1" into back-references — the inline reader then
 * arrived corrupted (SyntaxError) and window.expertStatsBackupReader was missing.
 */
class BackupReaderScript
{
    private static ?string $source = null;

    public static function source(): string
    {
        return self::$source ??= (string) file_get_contents(__DIR__.'/../../resources/js/backup-reader.js');
    }

    /** Only base64 characters: nothing a regex replacement can alter. */
    public static function dataUrl(): string
    {
        return 'data:text/javascript;base64,'.base64_encode(self::source());
    }
}
