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
        // The subject -> domain link now lives on subjects.domain_id. Dropping the
        // column also drops its aggregation CHECK constraint.
        Schema::table('domains', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subject_id');
            $table->dropColumn('aggregation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            // On PostgreSQL enum() is a varchar plus a CHECK, same as the original column.
            $table->enum('aggregation', ['weighted_average'])->nullable();
        });
    }
};
