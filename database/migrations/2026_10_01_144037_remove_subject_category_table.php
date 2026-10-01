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
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropForeign(['subject_category_id']);
            $table->string('name');
        });

        Schema::dropIfExists('subject_category');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('subject_category', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->foreignId('subject_category_id')->constrained('subject_category');
            $table->dropColumn('name');
        });
    }
};
