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
        Schema::table('domains', function (Blueprint $table) {
            $table->dropForeign('evaluation_nodes_subject_id_foreign');
            $table->dropColumn(['subject_id', 'aggregation']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            // On PostgreSQL enum() is a varchar plus a CHECK, same as the original column.
            $table->enum('aggregation', ['weighted_average'])->nullable();
        });
    }
};
