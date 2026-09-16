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
        Schema::create('evaluation_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('evaluation_node_id')->constrained('evaluation_nodes')->restrictOnDelete();
            // Sentinel 0 when the node's period_scope is `cursus`, never NULL, so the unique
            // index below stays null-safe and recompute upserts update in place.
            $table->unsignedSmallInteger('semester');
            $table->decimal('rounded_value', 2, 1);
            $table->timestamps();

            $table->unique(['user_id', 'evaluation_node_id', 'semester']);
            // user_id is covered by the leading column of the unique index; evaluation_node_id is not.
            $table->index('evaluation_node_id');
        });

        DB::statement('ALTER TABLE evaluation_results ADD CONSTRAINT evaluation_results_semester_check CHECK (semester BETWEEN 0 AND 8)');
        DB::statement('ALTER TABLE evaluation_results ADD CONSTRAINT evaluation_results_rounded_value_check CHECK (rounded_value >= 1.0 AND rounded_value <= 6.0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluation_results');
    }
};
