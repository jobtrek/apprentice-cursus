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
        Schema::create('evaluation_node_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->constrained('evaluation_nodes')->cascadeOnDelete();
            $table->foreignId('child_id')->constrained('evaluation_nodes')->cascadeOnDelete();
            $table->decimal('weight', 5, 2);
            // Default at the column level, so any attach() gets a created_at, not only
            // the ones going through EvaluationNode::addChild().
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['parent_id', 'child_id']);
            // parent_id is covered by the leading column of the unique index above; child_id is not.
            $table->index('child_id');
        });

        // Blocks the trivial self-loop. Longer cycles (A -> B -> A) cannot be expressed as a
        // CHECK and are enforced by EvaluationNode::addChild().
        DB::statement('ALTER TABLE evaluation_node_connections ADD CONSTRAINT evaluation_node_connections_no_self_loop_check CHECK (parent_id <> child_id)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluation_node_connections');
    }
};
