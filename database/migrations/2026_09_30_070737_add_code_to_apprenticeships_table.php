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
        Schema::table('apprenticeships', function (Blueprint $table) {
            $table->string('code')->nullable()->unique();
        });

        // The two tracks are fixed reference data that Azure group mapping resolves to,
        // so they must exist in every environment, not only where seeders run.
        foreach (['it' => 'IT', 'ec' => 'EC'] as $code => $name) {
            DB::table('apprenticeships')->updateOrInsert(
                ['code' => $code],
                ['name' => $name, 'created_at' => now(), 'updated_at' => now()],
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('apprenticeships', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn('code');
        });
    }
};
