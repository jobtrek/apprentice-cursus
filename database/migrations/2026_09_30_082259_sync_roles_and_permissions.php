<?php

use App\Support\RolesAndPermissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Seed roles and permissions, so a migrated database works without seeders.
     */
    public function up(): void
    {
        RolesAndPermissions::sync();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table(config('permission.table_names.roles'))->delete();
        DB::table(config('permission.table_names.permissions'))->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
