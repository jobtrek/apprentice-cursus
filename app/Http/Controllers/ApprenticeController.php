<?php

namespace App\Http\Controllers;

use App\Models\Grade;
use Inertia\Inertia;
use Inertia\Response;

class ApprenticeController extends Controller
{
    /**
     * Access is enforced by the `can:apprentices.view-list` route middleware.
     * TODO: limit trainers to their section once apprentices come from the database
     * (today the id is demo data on the frontend).
     */
    public function show(int $apprentice): Response
    {
        return Inertia::render('ApprenticeShow', [
            'apprenticeId' => $apprentice,
        ]);
    }

    /** Access to the grade is enforced by the `can:view,grade` route middleware. */
    public function grade(int $apprentice, Grade $grade): Response
    {
        return Inertia::render('GradeDetails', [
            ...DemoGrade::payload(),
            'apprenticeId' => $apprentice,
            'gradeId' => $grade->id,
            'can' => [
                'comment' => request()->user()->can('comment', $grade),
            ],
        ]);
    }
}
