<?php

namespace CXEngine\ExpertStatistics\Exceptions;

use RuntimeException;

/**
 * A file uploaded for the configuration audit was refused by the backend
 * (wrong type or structure); the message says why, for the user.
 */
class ConfigAuditFileRefusedException extends RuntimeException
{
}
