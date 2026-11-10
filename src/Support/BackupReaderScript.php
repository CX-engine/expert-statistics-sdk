<?php

namespace CXEngine\ExpertStatistics\Support;

/**
 * Source of resources/js/backup-reader.js, inlined once per page with @assets by
 * the AI Audit files tab (the package has no JS build and nothing to publish).
 */
class BackupReaderScript
{
    private static ?string $source = null;

    public static function source(): string
    {
        return self::$source ??= (string) file_get_contents(__DIR__.'/../../resources/js/backup-reader.js');
    }
}
