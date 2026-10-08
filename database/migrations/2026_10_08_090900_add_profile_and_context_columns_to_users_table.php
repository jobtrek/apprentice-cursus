<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
            $table->boolean('is_active')->default(true);
            $table->foreignId('apprenticeship_context_id')->nullable()->constrained('apprenticeship_contexts')->restrictOnDelete();
            $table->foreignId('coach_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('trainer_id')->nullable()->constrained('users')->nullOnDelete();
            // Last time the Entra account sync confirmed this user. NULL for accounts the sync never saw.
            $table->timestamp('synced_at')->nullable();

            // Postgres does not index foreign keys automatically.
            $table->index('apprenticeship_context_id');
            $table->index('coach_id');
            $table->index('trainer_id');

            // Users are SSO/local accounts with no "remember me" and no timestamps of their own.
            $table->dropColumn(['remember_token', 'created_at', 'updated_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->rememberToken();
            $table->timestamps();

            $table->dropConstrainedForeignId('trainer_id');
            $table->dropConstrainedForeignId('coach_id');
            $table->dropConstrainedForeignId('apprenticeship_context_id');
            $table->dropColumn(['azure_id', 'tenant_id', 'is_active', 'synced_at']);
        });
    }
};
