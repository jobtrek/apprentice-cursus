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
        Schema::table('domain_link_weights', function (Blueprint $table) {
            $table->decimal('weight', 3, 2)->default(0.00)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('domain_link_weights', function (Blueprint $table) {
            $table->decimal('weight', 3, 1)->default(0.00)->change();
        });
    }
};
