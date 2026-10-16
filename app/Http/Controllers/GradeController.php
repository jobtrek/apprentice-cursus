<?php

namespace App\Http\Controllers;

use App\Http\Requests\GradeRequest;
use App\Http\Requests\ReadScanRequest;
use App\Http\Resources\CommentResource;
use App\Http\Resources\GradeResource;
use App\Models\AverageNode;
use App\Models\Grade;
use App\Models\OcrScan;
use App\Notifications\GradeAddedNotification;
use App\Notifications\GradeDeletedNotification;
use App\Services\Ocr\GeminiService;
use App\Services\Ocr\JevService;
use App\Support\Gradebook\Gradebook;
use App\Support\Gradebook\GradebookTree;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Imagick;
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

    public function readScan(ReadScanRequest $request, GeminiService $gemini, JevService $jev): JsonResponse
    {
        abort_unless(config('ocr.enabled'), 404);

        $start = hrtime(true);

        // Pages 2–3 are read only when the page before has no grade or was cut off.
        // ponytail: synchronous; the cache and queue are in docs/ocr_specs/tasks.md.
        $path = $request->file('file')->getRealPath();
        $pdf = new Imagick;
        $pdf->pingImage($path);
        $pages = $pdf->getNumberImages();
        $firstText = null;
        $grade = null;
        $geminiCost = 0.0;
        $pagesRead = 0;
        $prices = config('ocr.prices');

        for ($n = 0; $n < min(3, $pages); $n++) {
            $page = new Imagick;
            $page->setResolution(200, 200);
            $page->readImage("{$path}[{$n}]");
            $page->setImageBackgroundColor('white');
            $page = $page->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
            $page->setImageFormat('png');

            $reading = $gemini->read($page->getImageBlob());
            $firstText ??= $reading['text'];
            $grade = $reading['grade'];
            $pagesRead = $n + 1;
            $geminiCost += ($reading['usage']->inputTokens * $prices['gemini']['input']
                + $reading['usage']->outputTokens * $prices['gemini']['output']) / 1e6;

            if ($grade !== null && ! $reading['truncated']) {
                break;
            }
        }

        $classification = $jev->classify(['first_page_text' => (string) $firstText]);

        // Only 1.0–6.0 in steps of 0.5 is a valid grade.
        if ($grade !== null && ($grade < 1 || $grade > 6 || fmod($grade * 2, 1) !== 0.0)) {
            $grade = null;
        }

        $scan = OcrScan::query()->create([
            'user_id' => $request->user()->id,
            'subject' => $classification['subject']->value,
            'grade' => $grade,
            'confidence' => $classification['confidence'],
            'flagged' => $grade === null || ($classification['confidence'] ?? 0) < OcrScan::FLAG_BELOW,
            'pages_read' => $pagesRead,
            'gemini_cost' => $geminiCost,
            'jev_cost' => $classification['usage']->inputTokens * $prices['jev']['input'] / 1e6,
            'duration_ms' => (int) round((hrtime(true) - $start) / 1e6),
        ]);

        return response()->json([
            'scan_id' => $scan->id,
            'subject' => $scan->subject,
            'confidence' => $scan->confidence,
            'grade' => $grade,
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
            DB::transaction(function () use ($request, $grade, $data, $file, &$oldPath, &$newPath): void {
                $this->lockSubject($request, (int) $data['average_node_id']);

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
                        ->update(['grade_id' => $grade->id, 'suggested_node_id' => $data['ocr_node_id'] ?? null]);
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

    /**
     * Shared lock on the subject: an admin moving it between plans (SubjectController) takes the
     * exclusive lock, so the two never interleave. The plan and leaf checks are repeated under
     * the lock, since the validated request may predate a move.
     */
    private function lockSubject(GradeRequest $request, int $nodeId): void
    {
        $node = AverageNode::query()->sharedLock()->find($nodeId);

        $valid = $node !== null
            && $node->children()->doesntExist()
            && $node->formationPlans()->whereKey($request->user()->formation_plan_id)->exists();

        if (! $valid) {
            throw ValidationException::withMessages(['average_node_id' => 'Cette branche ne fait pas partie de votre formation.']);
        }
    }

    public static function fileUrl(Grade $grade): ?string
    {
        return $grade->file_path === null ? null : route('grades.file', $grade);
    }
}
