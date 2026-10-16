<?php

namespace App\Http\Controllers;

use App\Models\OcrScan;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;

class OcrScanController extends Controller
{
    /** Days on the spend chart are local days, like the dates in the table. */
    private const string TIMEZONE = 'Europe/Zurich';

    /**
     * Cost and accuracy of the OCR. Access is enforced by the route's
     * `can:ocr.view-stats` middleware (local admin only).
     */
    public function __invoke(): Response
    {
        // A scan is right when the saved grade kept what the OCR suggested.
        $stats = OcrScan::query()
            ->leftJoin('grades', 'grades.id', '=', 'ocr_scans.grade_id')
            ->selectRaw('count(*) AS scans')
            ->selectRaw('count(grades.id) AS saved')
            // Ship gate (docs/ocr_specs/service-spec.md): flagged share, cost per test, unflagged accuracy.
            ->selectRaw('avg(ocr_scans.flagged::int) AS flagged_rate')
            ->selectRaw('avg(gemini_cost + jev_cost) AS average_cost')
            ->selectRaw('avg(duration_ms) AS average_duration_ms')
            ->selectRaw('avg((ocr_scans.grade IS NOT DISTINCT FROM grades.value AND ocr_scans.suggested_node_id IS NOT DISTINCT FROM grades.average_node_id)::int) FILTER (WHERE grades.id IS NOT NULL AND NOT ocr_scans.flagged) AS unflagged_accuracy')
            // Accuracy donut, saved scans only (the edit rate): each lands in exactly one bucket, a wrong grade first.
            ->selectRaw('count(*) FILTER (WHERE grades.id IS NOT NULL AND ocr_scans.grade IS DISTINCT FROM grades.value) AS wrong_grade')
            ->selectRaw('count(*) FILTER (WHERE grades.id IS NOT NULL AND ocr_scans.grade IS NOT DISTINCT FROM grades.value AND ocr_scans.suggested_node_id IS DISTINCT FROM grades.average_node_id) AS wrong_branch')
            ->toBase()
            ->first();

        $float = fn (?string $value): ?float => $value === null ? null : (float) $value;

        // ponytail: last 200 scans only, paginate when that stops being enough.
        $scans = OcrScan::query()
            ->with(['user:id,name', 'savedGrade:id,average_node_id,value'])
            ->latest('id')
            ->limit(200)
            ->get()
            ->map(fn (OcrScan $scan): array => [
                'id' => $scan->id,
                'created_at' => $scan->created_at->toIso8601String(),
                'user' => $scan->user->name,
                'subject' => $scan->subject,
                'grade' => $scan->grade,
                'confidence' => $scan->confidence,
                'flagged' => $scan->flagged,
                'pages_read' => $scan->pages_read,
                'gemini_cost' => (float) $scan->gemini_cost,
                'jev_cost' => (float) $scan->jev_cost,
                'duration_ms' => $scan->duration_ms,
                'saved_grade' => $scan->savedGrade?->value,
                // Null until a grade is saved from the scan.
                'grade_kept' => $scan->savedGrade ? $scan->grade === $scan->savedGrade->value : null,
                'subject_kept' => $scan->savedGrade ? $scan->suggested_node_id === $scan->savedGrade->average_node_id : null,
            ]);

        return Inertia::render('OcrScans', [
            'stats' => [
                'scans' => (int) $stats?->scans,
                'saved' => (int) $stats?->saved,
                'flaggedRate' => $float($stats?->flagged_rate),
                'averageCost' => $float($stats?->average_cost),
                'averageDurationMs' => $float($stats?->average_duration_ms),
                'unflaggedAccuracy' => $float($stats?->unflagged_accuracy),
            ],
            'charts' => [
                'spend' => $this->dailySpend(),
                // Ten 10 % bins; a confidence of exactly 1 goes in the last one. numeric: 0.3 stays in the 30 % bin.
                'confidence' => $this->bins(
                    OcrScan::query()->whereNotNull('confidence'),
                    'least(floor(confidence::numeric * 10), 9)',
                    array_map(fn (int $i): string => ($i * 10).'–'.($i * 10 + 10).' %', range(0, 9)),
                    fn (int $i): bool => $i < OcrScan::FLAG_BELOW * 10,
                ),
                // Fifteen 2 s bins, then one "30 s+" bin.
                'duration' => $this->bins(
                    OcrScan::query(),
                    'least(duration_ms / 2000, 15)',
                    [...array_map(fn (int $i): string => ($i * 2).'–'.($i * 2 + 2).' s', range(0, 14)), '30 s+'],
                    fn (int $i): bool => $i === 15,
                ),
                'accuracy' => [
                    'correct' => (int) $stats?->saved - (int) $stats?->wrong_grade - (int) $stats?->wrong_branch,
                    'wrongGrade' => (int) $stats?->wrong_grade,
                    'wrongBranch' => (int) $stats?->wrong_branch,
                ],
            ],
            'scans' => $scans,
        ]);
    }

    /**
     * Gemini and Jev spend per local day over the last 30 days, days without scans included.
     *
     * @return list<array{day: string, gemini: float, jev: float}>
     */
    private function dailySpend(): array
    {
        $from = now(self::TIMEZONE)->subDays(29)->startOfDay();

        // created_at is a UTC timestamp without time zone.
        $days = OcrScan::query()
            ->where('created_at', '>=', $from->utc())
            ->selectRaw("(created_at AT TIME ZONE 'UTC' AT TIME ZONE ?)::date AS day, sum(gemini_cost) AS gemini, sum(jev_cost) AS jev", [self::TIMEZONE])
            ->groupBy('day')
            ->toBase()
            ->get()
            ->keyBy('day');

        return array_map(function (int $i) use ($from, $days): array {
            $day = $from->addDays($i)->format('Y-m-d');

            return [
                'day' => $day,
                'gemini' => (float) ($days[$day]->gemini ?? 0),
                'jev' => (float) ($days[$day]->jev ?? 0),
            ];
        }, range(0, 29));
    }

    /**
     * One bar per label with its scan count, empty bins included.
     *
     * @param  Builder<OcrScan>  $query
     * @param  literal-string  $bin  SQL expression giving a bin index from 0 to count($labels) - 1
     * @param  list<string>  $labels
     * @param  callable(int): bool  $warn  whether the bin at this index is a warning
     * @return list<array{label: string, count: int, warn: bool}>
     */
    private function bins(Builder $query, string $bin, array $labels, callable $warn): array
    {
        $counts = $query->selectRaw("{$bin} AS bin, count(*) AS scans")
            ->groupBy('bin')
            ->toBase()
            ->pluck('scans', 'bin');

        return array_map(fn (string $label, int $i): array => [
            'label' => $label,
            'count' => (int) ($counts[$i] ?? 0),
            'warn' => $warn($i),
        ], $labels, array_keys($labels));
    }
}
