<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::rename('apprenticeships', 'formation_plans');
        Schema::rename('domains', 'average_nodes');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('average_nodes', 'domains');
        Schema::rename('formation_plans', 'apprenticeships');
    }
};
