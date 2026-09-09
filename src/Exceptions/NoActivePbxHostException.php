<?php

namespace CXEngine\ExpertStatistics\Exceptions;

use RuntimeException;

class NoActivePbxHostException extends RuntimeException
{
    public static function make(): self
    {
        return new self('No active PBX host could be resolved for the current request.');
    }
}
