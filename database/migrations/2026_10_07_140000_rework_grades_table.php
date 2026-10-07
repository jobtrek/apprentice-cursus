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
        Schema::table('grades', function (Blueprint $table) {
            $table->renameColumn('evaluation_node_id', 'domain_id');
            $table->dropForeign('grades_evaluation_node_id_foreign');
            $table->foreign('domain_id')->references('id')->on('domains')->restrictOnDelete();
            $table->renameIndex('grades_evaluation_node_id_index', 'grades_domain_id_index');
            $table->foreignId('subject_id')->constrained('subjects')->restrictOnDelete();
            $table->foreignId('apprenticeship_period_id')->constrained('apprenticeship_periods')->restrictOnDelete();
            $table->dropColumn('semester');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            $table->dropConstrainedForeignId('apprenticeship_period_id');
            $table->dropConstrainedForeignId('subject_id');
            // The default only lets the column come back on a non-empty table; it is dropped below.
            $table->unsignedSmallInteger('semester')->default(1);
            $table->renameIndex('grades_domain_id_index', 'grades_evaluation_node_id_index');
            $table->dropForeign('grades_domain_id_foreign');
            $table->renameColumn('domain_id', 'evaluation_node_id');
            $table->foreign('evaluation_node_id')->references('id')->on('domains')->restrictOnDelete();
        });

        Schema::table('grades', function (Blueprint $table) {
            $table->index(['user_id', 'evaluation_node_id', 'semester']);
        });

        DB::statement('ALTER TABLE grades ALTER COLUMN semester DROP DEFAULT');
        DB::statement('ALTER TABLE grades ADD CONSTRAINT grades_semester_check CHECK (semester BETWEEN 1 AND 8)');
    }
};
