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
            $table->string('azure_id')->nullable()->unique()->after('id');
            $table->string('tenant_id')->nullable()->after('azure_id');
            $table->boolean('is_mp')->nullable()->after('email');
            $table->enum('role', ['apprentice', 'coach', 'trainer', 'admin', 'super_admin'])->after('is_mp');
            $table->enum('apprenticeship_name', ['IT', 'EC'])->nullable()->after('role');
            $table->foreignId('apprenticeship_id')->nullable()->after('apprenticeship_name')->constrained('apprenticeships')->nullOnDelete();
            $table->foreignId('coach_id')->nullable()->after('apprenticeship_id')->constrained('users')->nullOnDelete();
            $table->foreignId('trainer_id')->nullable()->after('coach_id')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('trainer_id');
            $table->dropConstrainedForeignId('coach_id');
            $table->dropConstrainedForeignId('apprenticeship_id');
            $table->dropColumn(['azure_id', 'tenant_id', 'is_mp', 'role', 'apprenticeship_name']);
        });
    }
};
