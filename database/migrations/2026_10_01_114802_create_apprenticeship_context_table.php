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
        Schema::create('apprenticeship_contexts', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_mp');
            $table->foreignId('apprenticeship_id')->constrained('apprenticeships');
            $table->foreignId('root_domain_id')->constrained('domains');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('apprenticeship_id');
            $table->foreignId('context_id')->nullable()->constrained('apprenticeship_contexts');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('context_id');
            $table->foreignId('apprenticeship_id')->nullable()->constrained('apprenticeships')->nullOnDelete();
            $table->index('apprenticeship_id');
        });

        Schema::dropIfExists('apprenticeship_contexts');
    }
};
