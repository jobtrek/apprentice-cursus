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
        Schema::table('grades', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('subject_id');
            $table->index('apprenticeship_period_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            $table->dropIndex(['apprenticeship_period_id']);
            $table->dropIndex(['subject_id']);
            $table->dropIndex(['user_id']);
        });
    }
};
