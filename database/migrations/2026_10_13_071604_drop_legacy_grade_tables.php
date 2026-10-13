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
            $table->dropColumn('apprenticeship_context_id');
        });

        Schema::table('grades', function (Blueprint $table) {
            $table->dropColumn(['subject_id', 'apprenticeship_period_id']);
        });

        Schema::dropIfExists('domain_link_weights');
        Schema::dropIfExists('domain_links');
        Schema::dropIfExists('apprenticeship_contexts');
        Schema::dropIfExists('domain_subject');
        Schema::dropIfExists('subjects');
        Schema::dropIfExists('apprenticeship_periods');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        throw new RuntimeException('This migration cannot be rolled back. Use migrate:fresh instead.');
    }
};
