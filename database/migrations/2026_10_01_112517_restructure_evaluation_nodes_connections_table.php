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
        Schema::drop('evaluation_node_connections');
        Schema::create('domain_links', function (Blueprint $table) {
            $table->foreignId('parent_id')->constrained('domains');
            $table->foreignId('child_id')->constrained('domains');
            $table->primary(['parent_id', 'child_id']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domain_links');

        // At rollback time the parent table is still called `domains`
        // (renamed back to `evaluation_nodes` only by the earlier migration's
        // down()), so constrain to `domains`. Rows dropped by up() are not recoverable.
        Schema::create('evaluation_node_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->constrained('domains')->cascadeOnDelete();
            $table->foreignId('child_id')->constrained('domains')->cascadeOnDelete();
            $table->decimal('weight', 5, 2);
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['parent_id', 'child_id']);
            $table->index('child_id');
        });

        DB::statement('ALTER TABLE evaluation_node_connections ADD CONSTRAINT evaluation_node_connections_no_self_loop_check CHECK (parent_id <> child_id)');
    }
};
