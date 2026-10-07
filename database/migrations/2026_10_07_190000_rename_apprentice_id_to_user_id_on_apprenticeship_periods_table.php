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
        Schema::table('apprenticeship_periods', function (Blueprint $table) {
            $table->dropForeign('apprenticeship_periods_apprentice_id_foreign');
            $table->renameColumn('apprentice_id', 'user_id');
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
            $table->renameIndex('apprenticeship_periods_apprentice_id_year_semester_index', 'apprenticeship_periods_user_id_year_semester_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('apprenticeship_periods', function (Blueprint $table) {
            $table->dropForeign('apprenticeship_periods_user_id_foreign');
            $table->renameColumn('user_id', 'apprentice_id');
            $table->foreign('apprentice_id')->references('id')->on('users')->restrictOnDelete();
            $table->renameIndex('apprenticeship_periods_user_id_year_semester_index', 'apprenticeship_periods_apprentice_id_year_semester_index');
        });
    }
};
