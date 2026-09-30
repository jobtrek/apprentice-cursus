<?php

namespace App\Console\Commands;

use App\Exceptions\ApprenticeSyncAbortedException;
use App\Services\ApprenticeDirectorySync;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('apprentices:sync')]
#[Description('Create, update and deactivate apprentice accounts from the Entra ID apprentice groups')]
class SyncApprentices extends Command
{
    public function handle(ApprenticeDirectorySync $sync): int
    {
        try {
            $result = $sync->run();
        } catch (ApprenticeSyncAbortedException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("Apprentices synced: {$result->created} created, {$result->updated} updated, {$result->deactivated} deactivated, {$result->skipped} skipped.");

        return self::SUCCESS;
    }
}
