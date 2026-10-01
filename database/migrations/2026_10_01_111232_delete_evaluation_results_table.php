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
        Schema::drop('evaluation_results');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restores the schema as it was just before the drop (post-refactor:
        // domain_id -> domains). Rows dropped by up() are not recoverable.
        Schema::create('evaluation_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('domain_id')->constrained('domains')->restrictOnDelete();
            $table->unsignedSmallInteger('semester');
            $table->decimal('rounded_value', 2, 1);
            $table->timestamps();

            $table->unique(['user_id', 'domain_id', 'semester']);
            $table->index('domain_id');
        });

        DB::statement('ALTER TABLE evaluation_results ADD CONSTRAINT evaluation_results_semester_check CHECK (semester BETWEEN 0 AND 8)');
        DB::statement('ALTER TABLE evaluation_results ADD CONSTRAINT evaluation_results_rounded_value_check CHECK (rounded_value >= 1.0 AND rounded_value <= 6.0)');
    }
};
