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
        // A tree: each node carries its parent and its weight within that parent.
        // A node without parent is the root, the final grade of a formation plan.
        Schema::create('average_nodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('average_nodes')->restrictOnDelete();
            $table->string('name');
            $table->decimal('rounding_step', 2, 1)->nullable();
            $table->decimal('weight', 3, 2)->nullable();

            // Postgres does not index foreign keys automatically.
            $table->index('parent_id');
        });

        DB::statement('ALTER TABLE average_nodes ADD CONSTRAINT average_nodes_no_self_parent_check CHECK (parent_id <> id)');
        DB::statement('ALTER TABLE average_nodes ADD CONSTRAINT average_nodes_weight_check CHECK (weight > 0 AND weight <= 1)');

        // A plan lists the nodes it uses, so an MP plan and a standard plan share every
        // node except their own variant branch. No direct link from a plan to its root.
        Schema::create('average_node_formation_plan', function (Blueprint $table) {
            $table->foreignId('formation_plan_id')->constrained('formation_plans')->cascadeOnDelete();
            $table->foreignId('average_node_id')->constrained('average_nodes')->cascadeOnDelete();

            $table->primary(['formation_plan_id', 'average_node_id']);
            // formation_plan_id is covered by the leading column of the primary key; average_node_id is not.
            $table->index('average_node_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('average_node_formation_plan');
        Schema::dropIfExists('average_nodes');
    }
};
