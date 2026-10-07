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
        Schema::table('domain_links', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropForeign(['child_id']);
            $table->foreign('parent_id')->references('id')->on('domains')->cascadeOnDelete();
            $table->foreign('child_id')->references('id')->on('domains')->cascadeOnDelete();
        });

        Schema::table('domain_link_weights', function (Blueprint $table) {
            $table->dropForeign(['domain_link_id']);
            $table->foreign('domain_link_id')->references('id')->on('domain_links')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('domain_link_weights', function (Blueprint $table) {
            $table->dropForeign(['domain_link_id']);
            $table->foreign('domain_link_id')->references('id')->on('domain_links');
        });

        Schema::table('domain_links', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropForeign(['child_id']);
            $table->foreign('parent_id')->references('id')->on('domains');
            $table->foreign('child_id')->references('id')->on('domains');
        });
    }
};
