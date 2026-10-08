<?php

use App\Support\RolesAndPermissions;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Creates every role and permission the app currently defines, idempotently,
     * so a freshly migrated database is usable without a separate db:seed step.
     */
    public function up(): void
    {
        RolesAndPermissions::sync();
    }

    /**
     * Role/permission assignments to users (model_has_roles, model_has_permissions,
     * role_has_permissions) cascade away with their role or permission row.
     */
    public function down(): void
    {
        Role::query()->delete();
        Permission::query()->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
