<?php

namespace App\Http\Controllers;

use App\Http\Resources\CommentResource;
use App\Http\Resources\GradeResource;
use App\Models\Grade;
use App\Support\Demo\DemoGrade;
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
        return Inertia::render('GradesDashboard', [
            'grades' => GradeResource::collection(
                $request->user()->grades()
                    ->with(GradeResource::RELATIONS)
                    ->orderBy('test_date')
                    ->orderBy('id')
                    ->get(),
            )->resolve(),
        ]);
    }

    /**
     * Access is enforced by the `can:view,grade` route middleware.
     * TODO: replace the demo PDF once grade files are served from the database.
     */
    public function show(Request $request, Grade $grade): Response
    {
        return Inertia::render('GradeDetails', [
            ...DemoGrade::props(),
            'comments' => CommentResource::forGrade($grade),
            'grade' => (new GradeResource($grade->load(GradeResource::RELATIONS)))->resolve(),
            'can' => [
                'comment' => $request->user()->can('comment', $grade),
            ],
        ]);
    }
}
