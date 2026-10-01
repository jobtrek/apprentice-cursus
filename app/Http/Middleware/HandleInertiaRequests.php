<?php

namespace App\Http\Middleware;

use App\Enums\Permission;
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
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user === null ? null : [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role?->value,
                    'apprenticeship_context_id' => $user->apprenticeship_context_id,
                ],
                'can' => [
                    'createGrade' => $user?->can(Permission::GradesCreate->value) ?? false,
                    'viewOwnGrades' => $user?->can(Permission::GradesViewOwn->value) ?? false,
                    'viewSupervisedGrades' => $user?->can(Permission::GradesViewSupervised->value) ?? false,
                    'managePortfolio' => $user?->can(Permission::PortfolioManageOwn->value) ?? false,
                    'viewApprentices' => $user?->can(Permission::ApprenticesViewList->value) ?? false,
                ],
            ],
        ];
    }
}
