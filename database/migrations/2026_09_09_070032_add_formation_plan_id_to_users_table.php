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
        // Separate from create_users_table: formation_plans has to exist first.
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('formation_plan_id')->nullable()->constrained('formation_plans')->nullOnDelete();

            // Postgres does not index foreign keys automatically.
            $table->index('formation_plan_id');
        });

        // Trainers follow whole plans (IT, EC…) rather than individual apprentices.
        Schema::create('users_formation_plans', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('formation_plan_id')->constrained('formation_plans')->cascadeOnDelete();

            $table->primary(['user_id', 'formation_plan_id']);
            // user_id is covered by the leading column of the primary key; formation_plan_id is not.
            $table->index('formation_plan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users_formation_plans');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('formation_plan_id');
        });
    }
};
