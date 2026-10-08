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
        Schema::create('domain_subject', function (Blueprint $table) {
            $table->foreignId('domain_id')->constrained('domains')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();

            // The primary key only covers lookups that start from the domain.
            $table->primary(['domain_id', 'subject_id']);
            $table->index('subject_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domain_subject');
    }
};
