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
        // Least privilege: leftover admin accounts fall back to the default role.
        DB::statement("UPDATE users SET role = 'apprentice' WHERE role IN ('admin', 'super_admin')");

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
