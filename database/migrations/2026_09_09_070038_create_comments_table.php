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
            $table->string('commentable_type');
            $table->unsignedBigInteger('commentable_id');
            $table->text('body');
            $table->timestamps();

            $table->index(['commentable_type', 'commentable_id']);
            // Postgres does not index foreign keys automatically; the restrictOnDelete
            // check on author_id would otherwise seq scan the whole table.
            $table->index('author_id');
        });

        DB::statement('ALTER TABLE comments ADD CONSTRAINT comments_body_length_check CHECK (char_length(body) <= 2000)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
