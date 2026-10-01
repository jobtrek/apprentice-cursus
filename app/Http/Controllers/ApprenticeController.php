<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Http\Resources\ApprenticeResource;
use App\Http\Resources\CommentResource;
use App\Http\Resources\GradeResource;
use App\Http\Resources\ProjectResource;
use App\Models\Grade;
use App\Models\Skill;
use App\Models\User;
use App\Support\Demo\DemoGrade;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ApprenticeController extends Controller
{
    /** Route middleware gates the permission; User::listedApprentices() scopes the rows. */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $apprentices = $user->listedApprentices()
            ->with(ApprenticeResource::RELATIONS)
            ->orderBy('name')
            ->get();

        // Grade statistics only for the apprentices the user may open.
        $stats = $this->gradeStats(
            $apprentices->filter(fn (User $apprentice): bool => $user->can('view', $apprentice))->modelKeys(),
        );

        $canManage = $user->can(Permission::SupervisionManage->value);

        return Inertia::render('ApprentisDashboard', [
            'apprentices' => collect(ApprenticeResource::collection($apprentices)->resolve())
                ->map(fn (array $row): array => [...$row, 'stats' => $stats[$row['id']] ?? null])
                ->all(),
            // Local admin: coaches offered in each row's select.
            'coaches' => $canManage
                ? User::role(UserRole::Coach->value)->orderBy('name')->get(['id', 'name'])
                : [],
            // Who a coach can ask to validate a self-assignment: trainers and the local admin for now.
            'validators' => $user->can(Permission::CoachingAssignSelf->value)
                ? User::role([UserRole::Trainer->value, UserRole::Admin->value])->orderBy('name')->get(['id', 'name'])
                : [],
            'can' => [
                'manageSupervision' => $canManage,
            ],
        ]);
    }

    /** Route middleware gates the permission; UserPolicy::view limits it to supervised apprentices. */
    public function show(Request $request, User $apprentice): Response
    {
        Gate::authorize('view', $apprentice);

        // Read-only portfolio; null when the supervisor may not see it.
        $portfolio = $request->user()->can(Permission::PortfolioViewSupervised->value)
            ? [
                'projects' => ProjectResource::collection(
                    $apprentice->projects()
                        ->with(['skills:id', 'screenshots'])
                        ->orderByDesc('date_start')
                        ->get(),
                )->resolve(),
                'skills' => Skill::query()->orderBy('name')->get(['id', 'name']),
            ]
            : null;

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
            'portfolio' => $portfolio,
        ]);
    }

    /**
     * The coach_id guard makes two coaches assigning themselves at once safe:
     * the slower one updates no row and is refused.
     */
    public function assign(Request $request, User $apprentice): RedirectResponse
    {
        Gate::authorize('assignSelf', $apprentice);

        $assigned = User::query()
            ->whereKey($apprentice->id)
            ->whereNull('coach_id')
            ->update(['coach_id' => $request->user()->id]);

        abort_if($assigned === 0, 409);

        return back();
    }

    /** The grade must belong to the apprentice in the URL and be viewable by the user. */
    public function grade(User $apprentice, Grade $grade): Response
    {
        Gate::authorize('view', $apprentice);
        abort_unless($grade->user_id === $apprentice->id, 404);
        Gate::authorize('view', $grade);

        return Inertia::render('GradeDetails', [
            ...DemoGrade::props(),
            'comments' => CommentResource::forGrade($grade),
            'apprenticeId' => $apprentice->id,
            'apprentice' => (new ApprenticeResource($apprentice->load(ApprenticeResource::RELATIONS)))->resolve(),
            'grade' => (new GradeResource($grade->load(GradeResource::RELATIONS)))->resolve(),
            'can' => [
                'comment' => request()->user()->can('comment', $grade),
            ],
        ]);
    }

    /**
     * One grouped query for every apprentice, instead of one per row.
     *
     * @param  array<int, int>  $apprenticeIds
     * @return array<int, array{grades_count: int, average: float|null, last_grade_date: string|null}>
     */
    private function gradeStats(array $apprenticeIds): array
    {
        $aggregates = Grade::query()
            ->toBase()
            ->whereIn('user_id', $apprenticeIds)
            ->groupBy('user_id')
            ->selectRaw('user_id, count(*) as grades_count, avg(value) as average, max(test_date) as last_grade_date')
            ->get()
            ->keyBy('user_id');

        $stats = [];

        foreach ($apprenticeIds as $id) {
            $row = $aggregates->get($id);

            $stats[$id] = [
                'grades_count' => (int) ($row->grades_count ?? 0),
                'average' => isset($row->average) ? round((float) $row->average, 1) : null,
                'last_grade_date' => isset($row->last_grade_date)
                    ? CarbonImmutable::parse($row->last_grade_date)->format('d.m.Y')
                    : null,
            ];
        }

        return $stats;
    }
}
