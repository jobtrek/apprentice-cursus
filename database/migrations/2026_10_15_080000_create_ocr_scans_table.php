<?php

use App\Support\RolesAndPermissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per PDF read by GradeController::readScan, for the OCR dashboard.
     * Only the readings and their cost: never the text of the test.
     */
    public function up(): void
    {
        Schema::create('ocr_scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            // Set when the apprentice saves a grade from this scan.
            $table->foreignId('grade_id')->nullable()->constrained('grades')->nullOnDelete();
            // The branch the dialog suggested; compared with the saved grade's branch.
            $table->foreignId('suggested_node_id')->nullable()->constrained('average_nodes')->nullOnDelete();
            $table->string('subject');
            $table->decimal('grade', 2, 1)->nullable();
            $table->float('confidence')->nullable();
            $table->boolean('flagged');
            $table->unsignedSmallInteger('pages_read');
            $table->decimal('gemini_cost', 10, 6);
            $table->decimal('jev_cost', 10, 6);
            $table->unsignedInteger('duration_ms');
            $table->timestamps();

            // Postgres does not index foreign keys automatically.
            $table->index('user_id');
            $table->index('grade_id');
            $table->index('suggested_node_id');
        });

        // Creates the new ocr.view-stats permission.
        RolesAndPermissions::sync();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ocr_scans');
    }
};
