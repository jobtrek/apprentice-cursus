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
            $table->foreignId('subject_id')->nullable();
            $table->foreign('subject_id', 'evaluation_nodes_subject_id_foreign')
                ->references('id')->on('subjects')->nullOnDelete();
            $table->index('subject_id', 'evaluation_nodes_subject_id_index');
            $table->string('aggregation')->nullable();
        });

        DB::statement("ALTER TABLE domains ADD CONSTRAINT evaluation_nodes_aggregation_check CHECK (aggregation IS NULL OR aggregation IN ('weighted_average'))");
    }
};
