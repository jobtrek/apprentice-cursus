<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * The account sync could not read a complete picture of Entra ID (Graph
 * failure or unconfigured group). Nothing was written.
 */
class AzureSyncFailedException extends RuntimeException
{
    public static function groupNotConfigured(string $group): self
    {
        return new self("Azure group [{$group}] has no group id configured.");
    }

    public static function membersLookupFailed(string $group): self
    {
        return new self("Microsoft Graph members lookup failed for group [{$group}].");
    }
}
