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
        Schema::create('domain_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->constrained('domains')->cascadeOnDelete();
            $table->foreignId('child_id')->constrained('domains')->cascadeOnDelete();

            $table->unique(['parent_id', 'child_id']);
            // parent_id is covered by the leading column of the unique index above; child_id is not.
            $table->index('child_id');
        });

        // Blocks the trivial self-loop. Longer cycles (A -> B -> A) cannot be expressed as a
        // CHECK and are enforced by Domain::linkChild().
        DB::statement('ALTER TABLE domain_links ADD CONSTRAINT domain_links_no_self_loop_check CHECK (parent_id <> child_id)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domain_links');
    }
};
