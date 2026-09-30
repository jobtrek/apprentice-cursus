<?php

namespace App\Services;

/**
 * Counts reported by ApprenticeDirectorySync::run().
 */
final readonly class ApprenticeSyncResult
{
    public function __construct(
        public int $created = 0,
        public int $deactivated = 0,
        public int $skipped = 0,
    ) {}
}
