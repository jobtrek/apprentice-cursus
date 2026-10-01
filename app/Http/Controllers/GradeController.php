<?php

namespace App\Http\Controllers;

use App\Http\Resources\GradeResource;
use App\Models\Grade;
use App\Support\Demo\DemoGrade;
use App\Support\Gradebook\GradebookTree;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GradeController extends Controller
{
    /**
     * Access is enforced by the route's `can:grades.view-own` permission middleware.
     */
    public function dashboard(Request $request): Response
    {
        $user = $request->user()->load('apprenticeship');

        return Inertia::render('GradesDashboard', [
            'grades' => GradeResource::collection(
                $user->grades()
                    ->with(GradeResource::RELATIONS)
                    ->withCount('comments')
                    ->orderBy('test_date')
                    ->orderBy('id')
                    ->get(),
            )->resolve(),
            'tree' => GradebookTree::for($user),
        ]);
    }

    /**
     * Access is enforced by the `can:view,grade` route middleware.
     * TODO: replace the demo payload once grade files and comments are served from the database.
     */
    public function show(Request $request, Grade $grade): Response
    {
        return Inertia::render('GradeDetails', [
            ...DemoGrade::props(),
            'grade' => (new GradeResource($grade->load(GradeResource::RELATIONS)))->resolve(),
            'can' => [
                'comment' => $request->user()->can('comment', $grade),
            ],
        ]);
    }
}
