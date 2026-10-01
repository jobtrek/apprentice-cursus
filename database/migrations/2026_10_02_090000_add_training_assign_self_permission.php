<?php

use App\Support\RolesAndPermissions;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Create the `training.assign-self` permission on already-migrated
     * databases and give it to the trainer role (idempotent).
     */
    public function up(): void
    {
        RolesAndPermissions::sync();
    }

    public function down(): void
    {
        Permission::query()
            ->where('name', 'training.assign-self')
            ->where('guard_name', 'web')
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
