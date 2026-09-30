<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * See docs/adr/ADR.md (2026-09-23): Entra ID is the only source of roles, there is no
 * admin role, no default role, and trainers are scoped by section rather than assigned.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('apprentice', 'coach', 'trainer'))");
        DB::statement('ALTER TABLE users ALTER COLUMN role DROP DEFAULT');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('trainer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('trainer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->index('trainer_id');
        });

        DB::statement("ALTER TABLE users ALTER COLUMN role SET DEFAULT 'apprentice'");
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('apprentice', 'coach', 'trainer', 'admin', 'super_admin'))");
    }
};
