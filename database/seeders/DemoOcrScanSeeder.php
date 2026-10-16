<?php

namespace Database\Seeders;

use App\Enums\OcrSubject;
use App\Models\Grade;
use App\Models\OcrScan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Fills the OCR dashboard (/ocr-scans) with plausible scans spread over the
 * last 30 days, some of them linked to the demo grades so the accuracy chart
 * has right and corrected readings.
 *
 * LOCAL ONLY: never run outside the local environment. Skipped when the demo
 * accounts already have scans, so reseeding never duplicates anything.
 */
class DemoOcrScanSeeder extends Seeder
{
    public const int COUNT = 60;

    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $users = User::query()->whereIn('email', DemoGradeSeeder::emails())->pluck('id')->all();

        if ($users === [] || OcrScan::query()->whereIn('user_id', $users)->exists()) {
            return;
        }

        // Same data on every run, without reseeding the global mt_rand.
        $random = new Randomizer(new Mt19937(42));

        // Common subjects first; OTHER and Allemand read with lower confidence.
        $subjects = [
            ...array_fill(0, 5, OcrSubject::Maths), ...array_fill(0, 4, OcrSubject::Anglais),
            ...array_fill(0, 3, OcrSubject::Ecg), ...array_fill(0, 2, OcrSubject::Francais),
            OcrSubject::Allemand, OcrSubject::Histoire, OcrSubject::EcoDroit, OcrSubject::Other,
        ];
        $grades = Grade::query()->whereIn('user_id', $users)->get()->all();

        DB::transaction(function () use ($random, $users, $subjects, $grades): void {
            for ($i = 0; $i < self::COUNT; $i++) {
                $subject = $subjects[$random->getInt(0, count($subjects) - 1)];
                $unsure = in_array($subject, [OcrSubject::Other, OcrSubject::Allemand], true);
                $confidence = $unsure ? $random->getInt(20, 70) / 100 : $random->getInt(55, 99) / 100;
                $pages = $random->getInt(1, 10) <= 8 ? 1 : $random->getInt(2, 3);
                // One grade not found in eight.
                $value = $random->getInt(1, 8) === 1 ? null : $random->getInt(6, 12) / 2;
                $grade = array_shift($grades);

                (new OcrScan)->forceFill([
                    'user_id' => $grade->user_id ?? $users[$random->getInt(0, count($users) - 1)],
                    'grade_id' => $grade?->id,
                    // One branch in five corrected by the apprentice.
                    'suggested_node_id' => $grade && $random->getInt(1, 5) > 1 ? $grade->average_node_id : null,
                    'subject' => $subject->value,
                    // One grade in four corrected by the apprentice.
                    'grade' => $grade && $random->getInt(1, 4) > 1 ? $grade->value : $value,
                    'confidence' => $confidence,
                    'flagged' => $value === null || $confidence < OcrScan::FLAG_BELOW,
                    'pages_read' => $pages,
                    'gemini_cost' => $pages * $random->getInt(80, 300) / 100_000,
                    'jev_cost' => $random->getInt(10, 40) / 100_000,
                    // A few slow scans over 30 s.
                    'duration_ms' => $random->getInt(1, 15) === 1 ? $random->getInt(30_000, 45_000) : $pages * $random->getInt(3_000, 9_000),
                    'created_at' => now()->subDays($random->getInt(0, 29))->setTime($random->getInt(7, 18), $random->getInt(0, 59)),
                ])->save();
            }
        });
    }
}
