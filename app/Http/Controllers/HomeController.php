<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Http\Resources\GradeResource;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Home', [
            'recentGrades' => $user->can(Permission::GradesViewOwn->value)
                ? GradeResource::collection(
                    $user->grades()
                        ->with(GradeResource::RELATIONS)
                        ->latest('test_date')
                        ->latest('id')
                        ->limit(4)
                        ->get(),
                )->resolve()
                : [],
        ]);
    }
}
