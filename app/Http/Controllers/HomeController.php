<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Support\ApprenticeList;
use App\Support\Gradebook\Gradebook;
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

        return Inertia::render('Home', [
            // Every grade: the apprentice dashboard lists them and charts the averages.
            ...($ownGrades ? Gradebook::for($user) : Gradebook::EMPTY),
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
