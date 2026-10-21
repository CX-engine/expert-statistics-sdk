<?php

namespace CXEngine\ExpertStatistics\Exceptions;

use RuntimeException;

/**
 * Thrown when the backend has no 3CX configuration audit for the active host
 * yet (404: no backup analysed). Caught by Livewire\ConfigAudit\ConfigAudit
 * to show an empty state instead of an error.
 */
class ConfigAuditNotAvailableException extends RuntimeException
{
    public static function make(): self
    {
        return new self('No configuration audit is available for this host yet.');
    }
}
