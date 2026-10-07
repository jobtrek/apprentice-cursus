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
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('context_id', 'apprenticeship_context_id');
            $table->dropForeign('users_context_id_foreign');
            $table->foreign('apprenticeship_context_id')->references('id')->on('apprenticeship_contexts')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign('users_apprenticeship_context_id_foreign');
            $table->renameColumn('apprenticeship_context_id', 'context_id');
            $table->foreign('context_id')->references('id')->on('apprenticeship_contexts');
        });
    }
};
