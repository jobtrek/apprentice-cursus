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
        Schema::create('evaluation_nodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->string('name');
            $table->string('aggregation')->nullable();
            $table->decimal('rounding_step', 2, 1)->nullable();
            $table->string('period_scope');
            $table->string('variant')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('subject_id');
        });

        DB::statement("ALTER TABLE evaluation_nodes ADD CONSTRAINT evaluation_nodes_aggregation_check CHECK (aggregation IS NULL OR aggregation IN ('weighted_average'))");
        DB::statement("ALTER TABLE evaluation_nodes ADD CONSTRAINT evaluation_nodes_period_scope_check CHECK (period_scope IN ('semester', 'cursus'))");
        DB::statement("ALTER TABLE evaluation_nodes ADD CONSTRAINT evaluation_nodes_variant_check CHECK (variant IS NULL OR variant IN ('standard', 'mp'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluation_nodes');
    }
};
