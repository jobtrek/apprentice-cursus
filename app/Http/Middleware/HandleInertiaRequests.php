<?php

namespace App\Http\Middleware;

use App\Models\Grade;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
                'apprenticeship' => $request->user()?->apprenticeship?->code,
                'can' => $this->permissions($request->user()),
            ],
        ];
    }

    /**
     * The frontend's @can: computed from the policies so Vue never re-derives a rule.
     * These only hide UI; the routes and controllers enforce the same abilities.
     *
     * @return array<string, bool>
     */
    private function permissions(?User $user): array
    {
        if (! $user) {
            return [];
        }

        return [
            'createGrade' => $user->can('create', Grade::class),
            'viewPortfolio' => $user->can('viewAny', Project::class),
            'createProject' => $user->can('create', Project::class),
            'viewApprentices' => $user->can('viewAny', User::class),
            'viewAdministration' => $user->can('viewAdministration'),
        ];
    }
}
