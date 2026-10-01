<?php

use App\Support\RolesAndPermissions;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Create the `supervision.manage` permission on already-migrated databases
     * (idempotent: sync() only creates what is missing).
     */
    public function up(): void
    {
        RolesAndPermissions::sync();
    }

    public function down(): void
    {
        Permission::query()
            ->where('name', 'supervision.manage')
            ->where('guard_name', 'web')
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
