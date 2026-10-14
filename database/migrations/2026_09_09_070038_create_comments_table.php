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
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->constrained('users')->restrictOnDelete();
            // A comment targets exactly one project or one grade (see CHECK below).
            $table->foreignId('project_id')->nullable()->constrained('projects')->cascadeOnDelete();
            $table->foreignId('grade_id')->nullable()->constrained('grades')->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();

            // Postgres does not index foreign keys automatically; the restrictOnDelete
            // check on author_id would otherwise seq scan the whole table.
            $table->index('author_id');
            $table->index('project_id');
            $table->index('grade_id');
        });

        DB::statement('ALTER TABLE comments ADD CONSTRAINT comments_body_length_check CHECK (char_length(body) <= 2000)');
        DB::statement('ALTER TABLE comments ADD CONSTRAINT comments_single_target_check CHECK (num_nonnulls(project_id, grade_id) = 1)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
