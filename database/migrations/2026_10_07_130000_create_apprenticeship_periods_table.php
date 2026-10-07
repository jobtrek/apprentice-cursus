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
        Schema::create('apprenticeship_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apprentice_id')->constrained('users')->restrictOnDelete();
            $table->smallInteger('semester');
            $table->smallInteger('year')->storedAs('((semester + 1) / 2)::smallint');
            $table->date('start_date');
            $table->date('end_date');

            $table->index(['apprentice_id', 'year', 'semester']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apprenticeship_periods');
    }
};
