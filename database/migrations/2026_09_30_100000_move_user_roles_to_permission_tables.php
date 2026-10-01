<?php

use App\Models\User;
use App\Support\RolesAndPermissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Spatie roles become the only source of truth: seed roles/permissions,
     * backfill model_has_roles from users.role, then drop users.role.
     */
    public function up(): void
    {
        RolesAndPermissions::sync();

        DB::statement(<<<'SQL'
            INSERT INTO model_has_roles (role_id, model_type, model_id)
            SELECT r.id, ?, u.id
            FROM users u
            JOIN roles r ON r.name = u.role AND r.guard_name = 'web'
            WHERE NOT EXISTS (
                SELECT 1 FROM model_has_roles m WHERE m.model_id = u.id AND m.model_type = ?
            )
            SQL, [$this->morphClass(), $this->morphClass()]);

        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('apprentice');
        });

        DB::statement(<<<'SQL'
            UPDATE users
            SET role = r.name
            FROM model_has_roles m
            JOIN roles r ON r.id = m.role_id
            WHERE m.model_id = users.id AND m.model_type = ? AND r.name IN ('apprentice', 'coach', 'trainer')
            SQL, [$this->morphClass()]);

        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('apprentice', 'coach', 'trainer'))");

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** Honours the enforced morph map ('user'), which is what Spatie stores in model_type. */
    private function morphClass(): string
    {
        return (new User)->getMorphClass();
    }
};
