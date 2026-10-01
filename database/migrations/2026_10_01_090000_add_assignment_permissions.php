<?php

use App\Support\RolesAndPermissions;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Create the `supervision.manage` and `training.assign-self` permissions on
     * already-migrated databases and give the trainer role its new one
     * (idempotent: sync() only creates what is missing).
     */
    public function up(): void
    {
        RolesAndPermissions::sync();
    }

    public function down(): void
    {
        Permission::query()
            ->whereIn('name', ['supervision.manage', 'training.assign-self'])
            ->where('guard_name', 'web')
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
