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
        Schema::create('evaluation_node_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->constrained('evaluation_nodes')->cascadeOnDelete();
            $table->foreignId('child_id')->constrained('evaluation_nodes')->cascadeOnDelete();
            $table->decimal('weight', 5, 2);
            $table->timestamp('created_at')->nullable();

            $table->unique(['parent_id', 'child_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluation_node_connections');
    }
};
