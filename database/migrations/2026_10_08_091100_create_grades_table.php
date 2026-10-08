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
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('domain_id')->constrained('domains')->restrictOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->restrictOnDelete();
            $table->foreignId('apprenticeship_period_id')->constrained('apprenticeship_periods')->restrictOnDelete();
            $table->decimal('value', 2, 1);
            $table->date('test_date');
            $table->string('file_path')->nullable();
            $table->string('original_filename')->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            // Postgres does not index foreign keys automatically.
            $table->index('user_id');
            $table->index('domain_id');
            $table->index('subject_id');
            $table->index('apprenticeship_period_id');

            // A grade can only use a subject attached to its domain, and a pair cannot be
            // detached while grades use it.
            $table->foreign(['domain_id', 'subject_id'])
                ->references(['domain_id', 'subject_id'])
                ->on('domain_subject')
                ->restrictOnDelete();
            $table->index(['domain_id', 'subject_id']);
        });

        DB::statement('ALTER TABLE grades ADD CONSTRAINT grades_value_check CHECK (value >= 1.0 AND value <= 6.0)');
        // The MCD asks for `test_date <= today` too, but CURRENT_DATE is STABLE, not
        // IMMUTABLE, so Postgres rejects it in a CHECK. That one stays a FormRequest rule.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};
