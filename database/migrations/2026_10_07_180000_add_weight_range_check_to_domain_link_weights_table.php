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
        // The 0.00 default is dropped: it would violate the check below, and a weight must be given explicitly.
        Schema::table('domain_link_weights', function (Blueprint $table) {
            $table->decimal('weight', 3, 2)->change();
        });

        DB::statement('ALTER TABLE domain_link_weights ADD CONSTRAINT domain_link_weights_weight_check CHECK (weight > 0 AND weight <= 1)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE domain_link_weights DROP CONSTRAINT IF EXISTS domain_link_weights_weight_check');

        Schema::table('domain_link_weights', function (Blueprint $table) {
            $table->decimal('weight', 3, 2)->default(0.00)->change();
        });
    }
};
