<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Http\Resources\GradeResource;
use App\Support\ApprenticeList;
use App\Support\Gradebook\GradebookTree;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user()->load('apprenticeship');
        $ownGrades = $user->can(Permission::GradesViewOwn->value);

        return Inertia::render('Home', [
            // Every grade: the apprentice dashboard derives its statistics and charts from them.
            'grades' => $ownGrades
                ? GradeResource::collection(
                    $user->grades()
                        ->with(GradeResource::RELATIONS)
                        ->withCount('comments')
                        ->orderBy('test_date')
                        ->orderBy('id')
                        ->get(),
                )->resolve()
                : [],
            'tree' => $ownGrades ? GradebookTree::for($user) : null,
            'profile' => $ownGrades ? [
                'track' => $user->apprenticeship?->shortName(),
                'variant' => $user->apprenticeshipContext?->is_mp ? 'mp' : 'standard',
            ] : null,
            // Supervisor dashboard: the same rows as the apprentices page.
            'apprentices' => $user->can(Permission::ApprenticesViewList->value)
                ? ApprenticeList::for($user)
                : [],
            // Apprentices the coach or trainer could still take on.
            'assignableCount' => $user->assignableApprentices()->count(),
        ]);
    }
}
