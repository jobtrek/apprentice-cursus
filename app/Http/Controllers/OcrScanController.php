<?php

namespace App\Http\Controllers;

use App\Enums\OcrSubject;
use App\Models\OcrScan;
use Inertia\Inertia;
use Inertia\Response;

class OcrScanController extends Controller
{
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
            ->selectRaw('coalesce(sum(gemini_cost), 0) AS gemini_cost')
            ->selectRaw('coalesce(sum(jev_cost), 0) AS jev_cost')
            ->selectRaw('coalesce(sum(gemini_cost + jev_cost) FILTER (WHERE ocr_scans.created_at >= ?), 0) AS month_cost', [now()->startOfMonth()])
            ->selectRaw('avg(gemini_cost + jev_cost) AS average_cost')
            ->selectRaw('avg(flagged::int) AS flagged_rate')
            ->selectRaw('avg(duration_ms) AS average_duration_ms')
            ->selectRaw('count(grades.id) AS saved')
            ->selectRaw('avg((ocr_scans.grade IS NOT DISTINCT FROM grades.value)::int) FILTER (WHERE grades.id IS NOT NULL) AS grade_accuracy')
            ->selectRaw('avg((ocr_scans.suggested_node_id IS NOT DISTINCT FROM grades.average_node_id)::int) FILTER (WHERE grades.id IS NOT NULL) AS subject_accuracy')
            ->toBase()
            ->first();

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
                'subject' => OcrSubject::tryFrom($scan->subject)?->label() ?? $scan->subject,
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
                'geminiCost' => (float) $stats?->gemini_cost,
                'jevCost' => (float) $stats?->jev_cost,
                'monthCost' => (float) $stats?->month_cost,
                'averageCost' => $stats?->average_cost !== null ? (float) $stats->average_cost : null,
                'flaggedRate' => $stats?->flagged_rate !== null ? (float) $stats->flagged_rate : null,
                'averageDurationMs' => $stats?->average_duration_ms !== null ? (int) round((float) $stats->average_duration_ms) : null,
                'gradeAccuracy' => $stats?->grade_accuracy !== null ? (float) $stats->grade_accuracy : null,
                'subjectAccuracy' => $stats?->subject_accuracy !== null ? (float) $stats->subject_accuracy : null,
            ],
            'scans' => $scans,
        ]);
    }
}
