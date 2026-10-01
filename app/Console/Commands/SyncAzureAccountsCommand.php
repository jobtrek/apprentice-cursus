<?php

namespace App\Console\Commands;

use App\Services\AzureDirectorySync;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('azure:sync')]
#[Description('Sync local accounts with the mapped Entra ID groups')]
class SyncAzureAccountsCommand extends Command
{
    public function handle(AzureDirectorySync $sync): int
    {
        try {
            $result = $sync->run();
        } catch (Throwable $e) {
            $this->error("Account sync failed, nothing was changed: {$e->getMessage()}");

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Account sync done: %d created, %d updated, %d deactivated, %d skipped.',
            $result->created,
            $result->updated,
            $result->deactivated,
            $result->skipped,
        ));

        return self::SUCCESS;
    }
}
