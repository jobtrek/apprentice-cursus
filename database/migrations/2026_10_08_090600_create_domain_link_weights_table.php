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
        Schema::create('domain_link_weights', function (Blueprint $table) {
            // No explicit onDelete: a context or link is never deleted while still weighted
            // on purpose, left as NO ACTION rather than cascade or restrict.
            $table->foreignId('apprenticeship_context_id')->constrained('apprenticeship_contexts');
            $table->foreignId('domain_link_id')->constrained('domain_links')->cascadeOnDelete();
            $table->primary(['apprenticeship_context_id', 'domain_link_id']);

            $table->decimal('weight', 3, 2);

            // Postgres does not index foreign keys automatically. The composite PK leads
            // with apprenticeship_context_id, so domain_link_id lookups need their own index.
            $table->index('domain_link_id');
        });

        DB::statement('ALTER TABLE domain_link_weights ADD CONSTRAINT domain_link_weights_weight_check CHECK (weight > 0 AND weight <= 1)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domain_link_weights');
    }
};
