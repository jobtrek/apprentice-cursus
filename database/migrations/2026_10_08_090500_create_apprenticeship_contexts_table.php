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
        Schema::create('apprenticeship_contexts', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_mp');
            $table->foreignId('apprenticeship_id')->constrained('apprenticeships');
            $table->foreignId('root_domain_id')->constrained('domains');

            $table->unique(['apprenticeship_id', 'is_mp']);
            // Postgres does not index foreign keys automatically.
            $table->index('root_domain_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apprenticeship_contexts');
    }
};
