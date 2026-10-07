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
        // The root of the grade tree now lives on apprenticeship_contexts.root_domain_id.
        Schema::table('apprenticeships', function (Blueprint $table) {
            $table->dropConstrainedForeignId('evaluation_node_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('apprenticeships', function (Blueprint $table) {
            $table->foreignId('evaluation_node_id')->nullable()->constrained('domains')->restrictOnDelete();
        });
    }
};
