<?php

namespace App\Http\Middleware;

use App\Enums\Permission;
use App\Http\Resources\NotificationResource;
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

    private const NOTIFICATIONS_LIMIT = 50;

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
                    'formation_plan_id' => $user->formation_plan_id,
                    // The grade form lists the maturité subjects instead of the CFC ones.
                    'is_mp' => $user->formationPlan?->isMp() ?? false,
                ],
                'can' => [
                    'createGrade' => $user?->can(Permission::GradesCreate->value) ?? false,
                    'viewOwnGrades' => $user?->can(Permission::GradesViewOwn->value) ?? false,
                    'viewSupervisedGrades' => $user?->can(Permission::GradesViewSupervised->value) ?? false,
                    'managePortfolio' => $user?->can(Permission::PortfolioManageOwn->value) ?? false,
                    'viewApprentices' => $user?->can(Permission::ApprenticesViewList->value) ?? false,
                    'viewOcrStats' => $user?->can(Permission::OcrViewStats->value) ?? false,
                    // Off until the OCR is validated (config/ocr.php).
                    'useOcr' => config('ocr.enabled') && ($user?->can(Permission::GradesCreate->value) ?? false),
                ],
            ],
            // Lazy: evaluated on partial reloads only when asked for. The list is capped, the
            // count is not, so the bell badge stays right beyond the latest ones.
            'notifications' => fn () => $user === null ? [] : NotificationResource::collection(
                $user->notifications()->latest()->limit(self::NOTIFICATIONS_LIMIT)->get(),
            )->resolve(),
            'unreadNotificationsCount' => fn () => $user?->unreadNotifications()->count() ?? 0,
        ];
    }
}
