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
            $table->index('apprenticeship_context_id');
            // MP now lives on the apprentice's context (apprenticeship_contexts.is_mp).
            $table->dropColumn('is_mp');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Structure only: the dropped `is_mp` values are not restored.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_mp')->nullable();
            $table->dropIndex(['apprenticeship_context_id']);
        });
    }
};
