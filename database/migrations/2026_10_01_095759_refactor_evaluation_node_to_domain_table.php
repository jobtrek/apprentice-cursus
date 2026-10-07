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
        Schema::rename('evaluation_nodes', 'domains');
        Schema::table('domains', function (Blueprint $table) {
            $table->dropColumn('period_scope');
            $table->dropColumn('variant');
        });

        Schema::table('evaluation_results', function (Blueprint $table) {
            $table->dropForeign(['evaluation_node_id']);
            $table->renameColumn('evaluation_node_id', 'domain_id');
            $table->foreign('domain_id')->references('id')->on('domains');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evaluation_results', function (Blueprint $table) {
            $table->dropForeign(['domain_id']);
        });

        Schema::table('evaluation_results', function (Blueprint $table) {
            $table->renameColumn('domain_id', 'evaluation_node_id');
        });

        Schema::table('evaluation_results', function (Blueprint $table) {
            $table->foreign('evaluation_node_id')->references('id')->on('domains')->restrictOnDelete();
        });

        Schema::table('domains', function (Blueprint $table) {
            $table->string('period_scope')->nullable();
            $table->string('variant')->nullable();
        });

        // The up() drops the columns and loses their values; backfill so the
        // restored NOT NULL column succeeds on non-empty tables.
        DB::statement("UPDATE domains SET period_scope = 'semester' WHERE period_scope IS NULL");
        DB::statement('ALTER TABLE domains ALTER COLUMN period_scope SET NOT NULL');

        DB::statement("ALTER TABLE domains ADD CONSTRAINT evaluation_nodes_period_scope_check CHECK (period_scope IN ('semester', 'cursus'))");
        DB::statement("ALTER TABLE domains ADD CONSTRAINT evaluation_nodes_variant_check CHECK (variant IS NULL OR variant IN ('standard', 'mp'))");

        Schema::rename('domains', 'evaluation_nodes');
    }
};
