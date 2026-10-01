<?php

namespace App\Services;

/**
 * Counts of what one account sync run did.
 */
final readonly class AzureSyncResult
{
    public function __construct(
        public int $created = 0,
        public int $updated = 0,
        public int $deactivated = 0,
        public int $skipped = 0,
    ) {}

    /** @return array{created: int, updated: int, deactivated: int, skipped: int} */
    public function toArray(): array
    {
        return [
            'created' => $this->created,
            'updated' => $this->updated,
            'deactivated' => $this->deactivated,
            'skipped' => $this->skipped,
        ];
    }
}
