<?php

namespace App\Services;

/**
 * Counts reported by ApprenticeDirectorySync::run().
 *
 * `updated` counts existing accounts whose active flag, apprenticeship or role
 * actually changed; accounts that were already in line are not counted.
 */
final readonly class ApprenticeSyncResult
{
    public function __construct(
        public int $created = 0,
        public int $updated = 0,
        public int $deactivated = 0,
        public int $skipped = 0,
    ) {}
}
