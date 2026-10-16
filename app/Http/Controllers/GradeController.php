<?php

namespace App\Http\Controllers;

use App\Http\Requests\GradeRequest;
use App\Http\Requests\ReadScanRequest;
use App\Http\Resources\CommentResource;
use App\Http\Resources\GradeResource;
use App\Models\Grade;
use App\Models\OcrScan;
use App\Notifications\GradeAddedNotification;
use App\Notifications\GradeDeletedNotification;
use App\Services\Ocr\ScanReader;
use App\Support\Gradebook\Gradebook;
use App\Support\Gradebook\GradebookTree;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class GradeController extends Controller
{
    /**
     * Access is enforced by the route's `can:grades.view-own` permission middleware.
     */
    public function dashboard(Request $request): Response
    {
        return Inertia::render('GradesDashboard', Gradebook::for($request->user()));
    }

    public function store(GradeRequest $request): RedirectResponse
    {
        $grade = new Grade(['user_id' => $request->user()->id]);

        $this->saveGrade($request, $grade);

        Notification::send($request->user()->supervisors(), new GradeAddedNotification($grade));

        return back();
    }

    public function readScan(ReadScanRequest $request, ScanReader $reader): JsonResponse
    {
        abort_unless(config('ocr.enabled'), 404);

        $scan = $reader->read($request->file('file'), $request->user());

        return response()->json([
            'scan_id' => $scan->id,
            'subject' => $scan->subject,
            // The leaf of the apprentice's plan the subject matches, null when none does.
            'node_id' => $scan->suggested_node_id,
            'confidence' => $scan->confidence,
            'grade' => $scan->grade === null ? null : (float) $scan->grade,
            'flagged' => $scan->flagged,
            'duration_ms' => $scan->duration_ms,
        ]);
    }

    public function update(GradeRequest $request, Grade $grade): RedirectResponse
    {
        $this->saveGrade($request, $grade);

        return back();
    }

    public function destroy(Request $request, Grade $grade): RedirectResponse
    {
        $path = $grade->file_path;

        // Built before the delete: it snapshots the grade's name.
        $notification = new GradeDeletedNotification($grade);

        $grade->delete();

        // The scan goes first: the grade row, and with it the only record of the path, is
        // already gone, so a failing notification must not leave the file orphaned.
        if ($path !== null) {
            Storage::delete($path);
        }

        Notification::send($request->user()->supervisors(), $notification);

        return redirect()->route('grades.dashboard');
    }

    /**
     * Access is enforced by the `can:view,grade` route middleware.
     */
    public function show(Request $request, Grade $grade): Response
    {
        $user = $request->user();
        $canUpdate = $user->can('update', $grade);

        return Inertia::render('GradeDetails', [
            'pdfUrl' => self::fileUrl($grade),
            'comments' => CommentResource::forGrade($grade),
            'grade' => (new GradeResource($grade->load(GradeResource::RELATIONS)))->resolve(),
            'tree' => $canUpdate ? GradebookTree::for($user->load('formationPlan')) : null,
            'can' => [
                'comment' => $user->can('comment', $grade),
                'update' => $canUpdate,
                'delete' => $user->can('delete', $grade),
            ],
        ]);
    }

    public function file(Grade $grade): StreamedResponse
    {
        abort_if($grade->file_path === null, 404);

        return Storage::response($grade->file_path, $grade->load('averageNode', 'user')->fileName());
    }

    private function saveGrade(GradeRequest $request, Grade $grade): void
    {
        $data = $request->validated();
        $file = $data['file'] ?? null;
        $oldPath = null;
        $newPath = null;

        try {
            DB::transaction(function () use ($grade, $data, $file, &$oldPath, &$newPath): void {
                // Locked and re-read: a concurrent edit may have replaced the scan since the
                // request loaded the grade, and that newer scan is the one to clean up.
                if ($grade->exists) {
                    $oldPath = Grade::query()->lockForUpdate()->whereKey($grade->id)->value('file_path');
                    $grade->file_path = $oldPath;
                }

                $grade->fill([
                    'average_node_id' => $data['average_node_id'],
                    'value' => $data['value'],
                    'test_date' => $data['test_date'],
                ]);

                if ($data['is_oral'] ?? false) {
                    $grade->file_path = null;
                }

                $grade->save();

                // Links the OCR reading to the grade it produced, for the dashboard's accuracy.
                if (isset($data['ocr_scan_id'])) {
                    OcrScan::query()
                        ->whereKey($data['ocr_scan_id'])
                        ->where('user_id', $grade->user_id)
                        ->whereNull('grade_id')
                        ->update(['grade_id' => $grade->id]);
                }

                if ($file === null) {
                    return;
                }

                $newPath = $file->store("grades/{$grade->user_id}");

                if ($newPath === false) {
                    throw new RuntimeException("Could not store the scan of grade {$grade->id}.");
                }

                $grade->update(['file_path' => $newPath]);
            });
        } catch (Throwable $e) {
            if (is_string($newPath)) {
                Storage::delete($newPath);
            }

            throw $e;
        }

        if ($oldPath !== null && $oldPath !== $grade->file_path) {
            Storage::delete($oldPath);
        }
    }

    public static function fileUrl(Grade $grade): ?string
    {
        return $grade->file_path === null ? null : route('grades.file', $grade);
    }
}
