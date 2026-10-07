<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Structure only: no data is carried over, so this expects an empty `subjects` table.
     */
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subject_category_id');
            $table->string('name');
            $table->foreignId('domain_id')->constrained('domains');
            $table->index('domain_id');
        });

        Schema::drop('subject_category');
    }

    /**
     * Reverse the migrations.
     *
     * Structure only, like up(): fails on a populated `subjects` table.
     */
    public function down(): void
    {
        Schema::create('subject_category', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('domain_id');
            $table->dropColumn('name');

            $table->foreignId('subject_category_id')->constrained('subject_category');
            $table->index('subject_category_id');
        });
    }
};
