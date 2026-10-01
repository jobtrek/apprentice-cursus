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
        Schema::drop('evaluation_node_connections');
        Schema::create('domain_links', function (Blueprint $table) {
            $table->foreignId('parent_id')->constrained('domains');
            $table->foreignId('child_id')->constrained('domains');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
