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
        //
    }
};
