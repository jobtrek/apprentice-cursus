<?php

namespace App\Http\Controllers;

use App\Models\Grade;
use App\Support\Demo\DemoGrade;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GradeController extends Controller
{
    /**
     * Access is enforced by the `can:view,grade` route middleware.
     * TODO: replace the demo payload once grade files and comments are served from the database.
     */
    public function show(Request $request, Grade $grade): Response
    {
        return Inertia::render('GradeDetails', [
            ...DemoGrade::props(),
            'gradeId' => $grade->id,
            'can' => [
                'comment' => $request->user()->can('comment', $grade),
            ],
        ]);
    }
}
