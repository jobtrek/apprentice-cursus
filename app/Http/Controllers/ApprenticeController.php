<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Http\Resources\GradeResource;
use App\Http\Resources\ProjectResource;
use App\Models\Grade;
use App\Models\Skill;
use App\Models\User;
use App\Support\Demo\DemoGrade;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ApprenticeController extends Controller
{
    /**
     * The list itself is still static (apprentices.json); only the grade
     * statistics come from the database, keyed by apprentice id, and only for
     * the apprentices the user supervises.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $apprenticeIds = User::role(UserRole::Apprentice->value)
            ->with('roles')
            ->get()
            ->filter(fn (User $apprentice): bool => $user->supervises($apprentice))
            ->modelKeys();

        // One grouped query for every apprentice, instead of one per row.
        $aggregates = Grade::query()
            ->toBase()
            ->whereIn('user_id', $apprenticeIds)
            ->groupBy('user_id')
            ->selectRaw('user_id, count(*) as grades_count, avg(value) as average, max(test_date) as last_grade_date')
            ->get()
            ->keyBy('user_id');

        $stats = collect($apprenticeIds)->mapWithKeys(function (int $id) use ($aggregates): array {
            $row = $aggregates->get($id);

            return [$id => [
                'grades_count' => (int) ($row->grades_count ?? 0),
                'average' => isset($row->average) ? round((float) $row->average, 1) : null,
                'last_grade_date' => isset($row->last_grade_date)
                    ? CarbonImmutable::parse($row->last_grade_date)->format('d.m.Y')
                    : null,
            ]];
        });

        return Inertia::render('ApprentisDashboard', [
            'stats' => (object) $stats->all(),
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

    /** The grade must belong to the apprentice in the URL and be viewable by the user. */
    public function grade(User $apprentice, Grade $grade): Response
    {
        Gate::authorize('view', $apprentice);
        abort_unless($grade->user_id === $apprentice->id, 404);
        Gate::authorize('view', $grade);

        return Inertia::render('GradeDetails', [
            ...DemoGrade::props(),
            'apprenticeId' => $apprentice->id,
            'grade' => (new GradeResource($grade->load(GradeResource::RELATIONS)))->resolve(),
            'can' => [
                'comment' => request()->user()->can('comment', $grade),
            ],
        ]);
    }
}
