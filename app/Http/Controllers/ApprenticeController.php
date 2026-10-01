<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApprenticeResource;
use App\Http\Resources\GradeResource;
use App\Models\Grade;
use App\Models\User;
use App\Support\Demo\DemoGrade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ApprenticeController extends Controller
{
    /** Route middleware gates the permission; User::listedApprentices() scopes the rows. */
    public function index(Request $request): Response
    {
        return Inertia::render('ApprentisDashboard', [
            'apprentices' => ApprenticeResource::collection(
                $request->user()->listedApprentices()
                    ->with(ApprenticeResource::RELATIONS)
                    ->orderBy('name')
                    ->get(),
            )->resolve(),
        ]);
    }

    /** Route middleware gates the permission; UserPolicy::view limits it to supervised apprentices. */
    public function show(User $apprentice): Response
    {
        Gate::authorize('view', $apprentice);

        return Inertia::render('ApprenticeShow', [
            'apprenticeId' => $apprentice->id,
            'apprentice' => (new ApprenticeResource($apprentice->load(ApprenticeResource::RELATIONS)))->resolve(),
            'grades' => GradeResource::collection(
                $apprentice->grades()
                    ->with(GradeResource::RELATIONS)
                    ->orderBy('test_date')
                    ->orderBy('id')
                    ->get(),
            )->resolve(),
        ]);
    }

    /** The grade must belong to the apprentice in the URL and be viewable by the user. */
    public function grade(User $apprentice, Grade $grade): Response
    {
        Gate::authorize('view', $apprentice);
        abort_unless($grade->user_id === $apprentice->id, 404);
        Gate::authorize('view', $grade);

        return Inertia::render('GradeDetails', [
            ...DemoGrade::props(),
            'apprenticeId' => $apprentice->id,
            'apprentice' => (new ApprenticeResource($apprentice->load(ApprenticeResource::RELATIONS)))->resolve(),
            'grade' => (new GradeResource($grade->load(GradeResource::RELATIONS)))->resolve(),
            'can' => [
                'comment' => request()->user()->can('comment', $grade),
            ],
        ]);
    }
}
