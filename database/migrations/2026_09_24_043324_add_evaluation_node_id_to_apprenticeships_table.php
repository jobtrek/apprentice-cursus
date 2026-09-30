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
        Schema::table('apprenticeships', function (Blueprint $table) {
            // Root of the apprenticeship's grade tree. Restricted like the other
            // history-bearing keys: deleting a root would orphan every grade under it.
            $table->foreignId('evaluation_node_id')->nullable()->constrained('evaluation_nodes')->restrictOnDelete();

            // Postgres does not index foreign keys automatically.
            $table->index('evaluation_node_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('apprenticeships', function (Blueprint $table) {
            $table->dropConstrainedForeignId('evaluation_node_id');
        });
    }
};
