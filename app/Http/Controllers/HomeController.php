<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Http\Resources\GradeResource;
use App\Support\ApprenticeList;
use App\Support\Gradebook\GradebookTree;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user()->load('formationPlan');
        $ownGrades = $user->can(Permission::GradesViewOwn->value);

        $supervises = $user->can(Permission::ApprenticesViewList->value);

        $grades = $ownGrades
            ? $user->grades()
                ->withSemester()
                ->with(GradeResource::RELATIONS)
                ->withCount('comments')
                ->orderBy('test_date')
                ->orderBy('id')
                ->get()
            : new Collection;

        return Inertia::render('Home', [
            // Every grade: the apprentice dashboard derives its statistics and charts from them.
            'grades' => GradeResource::collection($grades)->resolve(),
            'tree' => $ownGrades ? GradebookTree::for($user, $grades) : null,
            'profile' => $ownGrades ? [
                'track' => $user->formationPlan?->shortName(),
                'variant' => $user->formationPlan?->isMp() ? 'mp' : 'standard',
            ] : null,
            // Supervisor dashboard: the same rows as the apprentices page.
            'apprentices' => $supervises ? ApprenticeList::for($user) : [],
            'latestGrades' => $supervises ? ApprenticeList::latestGrades($user) : [],
            'activity' => $supervises ? ApprenticeList::monthlyActivity($user) : [],
            // Apprentices the coach could still take on.
            'assignableCount' => $user->assignableApprentices()->count(),
        ]);
    }
}
