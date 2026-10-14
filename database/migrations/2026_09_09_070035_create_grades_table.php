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
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('average_node_id')->constrained('average_nodes')->restrictOnDelete();
            $table->decimal('value', 2, 1);
            $table->date('test_date');
            $table->string('file_path')->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            // Postgres does not index foreign keys automatically. The composite covers the
            // hot read path ("all grades of apprentice X") and the user_id restrictOnDelete
            // check via its leading column; average_node_id needs its own.
            $table->index(['user_id', 'average_node_id']);
            $table->index('average_node_id');
        });

        DB::statement('ALTER TABLE grades ADD CONSTRAINT grades_value_check CHECK (value >= 1.0 AND value <= 6.0)');
        // The MCD asks for `test_date <= today` too, but CURRENT_DATE is STABLE, not
        // IMMUTABLE, so Postgres rejects it in a CHECK. That one stays a FormRequest rule.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};
