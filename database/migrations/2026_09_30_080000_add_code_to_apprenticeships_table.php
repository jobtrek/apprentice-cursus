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
            $table->string('code', 2)->unique();
        });

        // Reference data, not seed data: the Entra role mapping resolves a user's
        // section to one of these rows by code, so both must exist in every environment.
        DB::table('apprenticeships')->insert([
            ['code' => 'it', 'name' => 'Informaticien·ne', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'ec', 'name' => 'Employé·e de commerce', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('apprenticeships')->whereIn('code', ['it', 'ec'])->delete();

        Schema::table('apprenticeships', function (Blueprint $table) {
            $table->dropColumn('code');
        });
    }
};
