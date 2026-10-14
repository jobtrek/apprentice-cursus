<?php

namespace App\Http\Controllers;

use App\Http\Requests\GradeRequest;
use App\Http\Resources\CommentResource;
use App\Http\Resources\GradeResource;
use App\Models\Grade;
use App\Support\Gradebook\GradebookTree;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $user = $request->user()->load('formationPlan');

        return Inertia::render('GradesDashboard', [
            'grades' => GradeResource::collection(
                $user->grades()
                    ->withSemester()
                    ->with(GradeResource::RELATIONS)
                    ->withCount('comments')
                    ->orderBy('test_date')
                    ->orderBy('id')
                    ->get(),
            )->resolve(),
            'tree' => GradebookTree::for($user),
        ]);
    }

    public function store(GradeRequest $request): RedirectResponse
    {
        $this->saveGrade($request, new Grade(['user_id' => $request->user()->id]));

        return back();
    }

    public function update(GradeRequest $request, Grade $grade): RedirectResponse
    {
        $this->saveGrade($request, $grade);

        return back();
    }

    public function destroy(Grade $grade): RedirectResponse
    {
        $path = $grade->file_path;

        $grade->delete();

        if ($path !== null) {
            Storage::delete($path);
        }

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
        $oldPath = $grade->file_path;
        $newPath = null;

        try {
            DB::transaction(function () use ($grade, $data, $file, &$newPath): void {
                $grade->fill([
                    'average_node_id' => $data['average_node_id'],
                    'value' => $data['value'],
                    'test_date' => $data['test_date'],
                ]);

                if ($data['is_oral'] ?? false) {
                    $grade->file_path = null;
                }

                $grade->save();

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
