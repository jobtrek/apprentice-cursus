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
        Schema::table('apprenticeship_contexts', function (Blueprint $table) {
            $table->unique(['apprenticeship_id', 'is_mp']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('apprenticeship_contexts', function (Blueprint $table) {
            $table->dropUnique(['apprenticeship_id', 'is_mp']);
        });
    }
};
