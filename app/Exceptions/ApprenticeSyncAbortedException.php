<?php

namespace App\Exceptions;

use App\Enums\AzureGroup;
use RuntimeException;
use Throwable;

/**
 * The apprentice directory sync stopped before writing anything (or rolled its
 * writes back): a partial view of Entra ID must never deactivate anybody.
 */
class ApprenticeSyncAbortedException extends RuntimeException
{
    public static function groupNotConfigured(AzureGroup $group): self
    {
        return new self("Apprentice sync aborted: no group id configured for [{$group->value}].");
    }

    public static function graphFailed(string $context, ?Throwable $previous = null): self
    {
        return new self("Apprentice sync aborted: Microsoft Graph {$context} failed.", 0, $previous);
    }

    public static function apprenticeshipMissing(string $name, ?Throwable $previous = null): self
    {
        return new self("Apprentice sync aborted: apprenticeship [{$name}] is not seeded.", 0, $previous);
    }
}
