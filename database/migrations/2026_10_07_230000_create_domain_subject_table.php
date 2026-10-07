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
        Schema::create('domain_subject', function (Blueprint $table) {
            $table->foreignId('domain_id')->constrained('domains')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();

            // The primary key only covers lookups that start from the domain.
            $table->primary(['domain_id', 'subject_id']);
            $table->index('subject_id');
        });

        DB::statement('INSERT INTO domain_subject (domain_id, subject_id) SELECT domain_id, id FROM subjects');

        Schema::table('subjects', function (Blueprint $table) {
            // Postgres drops subjects_domain_id_index along with the column.
            $table->dropConstrainedForeignId('domain_id');
        });

        // A grade can only use a subject attached to its domain, and a pair cannot be
        // detached while grades use it. The index keeps that check off a sequential scan.
        Schema::table('grades', function (Blueprint $table) {
            $table->foreign(['domain_id', 'subject_id'])
                ->references(['domain_id', 'subject_id'])
                ->on('domain_subject')
                ->restrictOnDelete();
            $table->index(['domain_id', 'subject_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * Only works while every subject is attached to exactly one domain: a subject with
     * several domains keeps an arbitrary one, and a subject with none fails the NOT NULL.
     */
    public function down(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            $table->dropForeign(['domain_id', 'subject_id']);
            $table->dropIndex(['domain_id', 'subject_id']);
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->foreignId('domain_id')->nullable()->constrained('domains');
            $table->index('domain_id');
        });

        DB::statement('UPDATE subjects SET domain_id = domain_subject.domain_id FROM domain_subject WHERE domain_subject.subject_id = subjects.id');
        DB::statement('ALTER TABLE subjects ALTER COLUMN domain_id SET NOT NULL');

        Schema::dropIfExists('domain_subject');
    }
};
