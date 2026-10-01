<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Leftover admin accounts are disabled, not demoted: the CHECK needs a valid role, but is_active = false blocks all access.
        DB::statement("UPDATE users SET role = 'apprentice', is_active = false WHERE role IN ('admin', 'super_admin')");

        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('apprentice', 'coach', 'trainer'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('apprentice', 'coach', 'trainer', 'admin', 'super_admin'))");
    }
};
