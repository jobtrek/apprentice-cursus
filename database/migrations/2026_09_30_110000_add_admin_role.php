<?php

use App\Support\RolesAndPermissions;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Create the local-only admin role on already-migrated databases
     * (idempotent: sync() only creates what is missing).
     */
    public function up(): void
    {
        RolesAndPermissions::sync();
    }

    public function down(): void
    {
        Role::query()->where('name', 'admin')->where('guard_name', 'web')->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
