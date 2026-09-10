<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('azure_id')->nullable()->unique();
            $table->string('tenant_id')->nullable();
            $table->boolean('is_mp')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('role')->default('apprentice');
            $table->foreignId('apprenticeship_id')->nullable()->constrained('apprenticeships')->nullOnDelete();
            $table->foreignId('coach_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('trainer_id')->nullable()->constrained('users')->nullOnDelete();

            // Postgres does not index foreign keys automatically. Without these, every
            // nullOnDelete check and every "who does this coach follow" lookup is a seq scan.
            $table->index('apprenticeship_id');
            $table->index('coach_id');
            $table->index('trainer_id');
        });

        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('apprentice', 'coach', 'trainer', 'admin', 'super_admin'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('trainer_id');
            $table->dropConstrainedForeignId('coach_id');
            $table->dropConstrainedForeignId('apprenticeship_id');
            $table->dropColumn(['azure_id', 'tenant_id', 'is_mp', 'is_active', 'role']);
        });
    }
};
