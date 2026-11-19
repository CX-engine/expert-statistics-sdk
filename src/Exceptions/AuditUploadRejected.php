<?php

namespace CXEngine\ExpertStatistics\Exceptions;

use RuntimeException;

/**
 * A file of the AI Audit "files" tab refused by AuditUploadInspector before it
 * is forwarded. The message is translated and safe to show.
 */
class AuditUploadRejected extends RuntimeException
{
    public function __construct(public readonly string $reason, string $message)
    {
        parent::__construct($message, 422);
    }

    /**
     * @param  array<string, string|int>  $parameters
     */
    public static function because(string $reason, array $parameters = []): self
    {
        return new self($reason, __("expert-statistics::pbx.config.auditFiles.errors.{$reason}", $parameters));
    }
}
