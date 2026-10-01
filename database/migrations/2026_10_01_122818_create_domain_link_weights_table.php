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
        Schema::create('domain_link_weights', function (Blueprint $table) {
            $table->foreignId('apprenticeship_context_id')->constrained('apprenticeship_contexts');
            $table->foreignId('domain_link_id')->constrained('domain_links');
            $table->primary(['apprenticeship_context_id', 'domain_link_id']);

            $table->decimal('weight', 3, 1)->default(0.00);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domain_link_weights');
    }
};
