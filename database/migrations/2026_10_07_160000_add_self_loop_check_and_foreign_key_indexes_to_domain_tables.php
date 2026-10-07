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
        DB::statement('ALTER TABLE domain_links ADD CONSTRAINT domain_links_no_self_loop_check CHECK (parent_id <> child_id)');

        Schema::table('domain_links', function (Blueprint $table) {
            $table->index('child_id');
        });

        Schema::table('domain_link_weights', function (Blueprint $table) {
            $table->index('domain_link_id');
        });

        Schema::table('apprenticeship_contexts', function (Blueprint $table) {
            $table->index('root_domain_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('apprenticeship_contexts', function (Blueprint $table) {
            $table->dropIndex(['root_domain_id']);
        });

        Schema::table('domain_link_weights', function (Blueprint $table) {
            $table->dropIndex(['domain_link_id']);
        });

        Schema::table('domain_links', function (Blueprint $table) {
            $table->dropIndex(['child_id']);
        });

        DB::statement('ALTER TABLE domain_links DROP CONSTRAINT IF EXISTS domain_links_no_self_loop_check');
    }
};
