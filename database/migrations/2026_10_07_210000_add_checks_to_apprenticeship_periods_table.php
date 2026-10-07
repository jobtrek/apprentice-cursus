<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE apprenticeship_periods ADD CONSTRAINT apprenticeship_periods_semester_check CHECK (semester BETWEEN 1 AND 8)');
        DB::statement('ALTER TABLE apprenticeship_periods ADD CONSTRAINT apprenticeship_periods_dates_check CHECK (end_date >= start_date)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE apprenticeship_periods DROP CONSTRAINT IF EXISTS apprenticeship_periods_dates_check');
        DB::statement('ALTER TABLE apprenticeship_periods DROP CONSTRAINT IF EXISTS apprenticeship_periods_semester_check');
    }
};
