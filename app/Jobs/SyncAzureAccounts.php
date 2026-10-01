<?php

namespace App\Jobs;

use App\Services\AzureDirectorySync;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Daily account sync. Unique so two runs never overlap; a failed run changes
 * nothing and is retried.
 */
class SyncAzureAccounts implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 300];

    public function handle(AzureDirectorySync $sync): void
    {
        $sync->run();
    }
}
