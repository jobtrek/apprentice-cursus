<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The intake mail a grade came from, so emails:fetch never imports the same mail twice.
     * NULL for grades added in the app; Postgres lets NULLs repeat under a unique index.
     */
    public function up(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            $table->string('source_mailbox')->nullable();
            $table->unsignedBigInteger('source_uid')->nullable();

            $table->unique(['source_mailbox', 'source_uid']);
        });
    }

    public function down(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            $table->dropUnique(['source_mailbox', 'source_uid']);
            $table->dropColumn(['source_mailbox', 'source_uid']);
        });
    }
};
