<?php

namespace CXEngine\ExpertStatistics\Exceptions;

use RuntimeException;

/**
 * Thrown when the backend refuses an ai-helper mutation (create/schedule a
 * report, create a resource group) with a 403 because the active host's
 * `ai_activated` flag is off. Caught specifically by
 * Livewire\Docs\DocsAssistantChat::confirmAction() to show a friendly
 * "ask an administrator to activate AI features" message instead of a
 * generic failure.
 */
class AiFeaturesNotActivatedException extends RuntimeException
{
    public static function make(): self
    {
        return new self('AI features are not activated for this host.');
    }
}
