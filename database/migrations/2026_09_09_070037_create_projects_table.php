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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('title');
            $table->string('organization')->nullable();
            $table->text('description');
            $table->string('responsibilities')->nullable();
            $table->string('technologies')->nullable();
            $table->string('repository_url')->nullable();
            $table->string('demo_path')->nullable();
            $table->date('date_start');
            $table->date('date_end')->nullable();
            $table->timestamps();

            // Postgres does not index foreign keys automatically; the restrictOnDelete
            // check on user_id would otherwise seq scan the whole table.
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
