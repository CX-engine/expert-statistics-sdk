<?php

namespace CXEngine\ExpertStatistics\Exceptions;

use RuntimeException;

/**
 * Thrown when the backend rejects a training key + email pair: unknown
 * (404) or expired (410). Caught by Livewire\Training\Training to show the
 * error on the key field instead of a generic failure.
 */
class InvalidTrainingKeyException extends RuntimeException
{
    public function __construct(string $message, public readonly bool $expired = false)
    {
        parent::__construct($message);
    }

    public static function make(bool $expired = false): self
    {
        return new self($expired ? 'This training key has expired.' : 'Invalid training key or email.', $expired);
    }
}
