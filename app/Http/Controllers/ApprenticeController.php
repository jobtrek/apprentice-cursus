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
use Database\Seeders\ApprenticeshipSeeder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ApprenticeController extends Controller
{
    /**
     * Apprentices the user supervises (coach and trainer: the ones assigned to
     * them, local admin: everyone), with their grade statistics.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $apprentices = $this->apprentices()
            ->filter(fn (User $apprentice): bool => $user->supervises($apprentice))
            ->values();

        $canManage = $user->can(Permission::SupervisionManage->value);
        // Which "Ajouter" button the user gets. The local admin passes every
        // check, but assigns coaches from the selects instead.
        $assignSelfAs = match (true) {
            $canManage => null,
            $user->can(Permission::CoachingAssignSelf->value) => 'coach',
            $user->can(Permission::TrainingAssignSelf->value) => 'trainer',
            default => null,
        };

        $stats = $this->gradeStats($apprentices->map(fn (User $apprentice): int => $apprentice->id)->all());

        return Inertia::render('ApprentisDashboard', [
            'apprentices' => $apprentices->map(fn (User $apprentice): array => [
                ...$this->summary($apprentice),
                'stats' => $stats[$apprentice->id],
            ])->all(),
            // Apprentices the "Ajouter" button offers: no coach yet (coach), or
            // no trainer yet and in the trainer's section (trainer).
            'assignable' => $assignSelfAs === null
                ? []
                : $this->apprentices()
                    ->filter(fn (User $apprentice): bool => $user->can(
                        $assignSelfAs === 'coach' ? 'assignSelfAsCoach' : 'assignSelfAsTrainer',
                        $apprentice,
                    ))
                    ->map(fn (User $apprentice): array => [
                        'id' => $apprentice->id,
                        'name' => $apprentice->name,
                        'track' => $this->track($apprentice),
                    ])
                    ->values()
                    ->all(),
            // Local admin: coaches and trainers offered in each row's selects.
            'coaches' => $canManage
                ? User::role(UserRole::Coach->value)->orderBy('name')->get(['id', 'name'])
                : [],
            // The front only offers the trainers of the apprentice's section.
            'trainers' => $canManage
                ? User::role(UserRole::Trainer->value)
                    ->with('apprenticeship')
                    ->orderBy('name')
                    ->get()
                    ->map(fn (User $trainer): array => [
                        'id' => $trainer->id,
                        'name' => $trainer->name,
                        'track' => $this->track($trainer),
                    ])
                    ->all()
                : [],
            'can' => [
                'assignSelfAs' => $assignSelfAs,
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
            'apprentice' => $this->summary($apprentice->load(['apprenticeship', 'coach', 'trainer'])),
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

    /**
     * @return Collection<int, User>
     */
    private function apprentices(): Collection
    {
        return User::role(UserRole::Apprentice->value)
            ->with(['roles', 'apprenticeship', 'coach', 'trainer'])
            ->orderBy('name')
            ->get();
    }

    /**
     * Shape of `ApprenticeSummary` in resources/js/types/apprentice.ts.
     *
     * @return array<string, mixed>
     */
    private function summary(User $apprentice): array
    {
        return [
            'id' => $apprentice->id,
            'name' => $apprentice->name,
            'track' => $this->track($apprentice),
            'is_active' => $apprentice->is_active,
            'coach' => $apprentice->coach === null ? null : [
                'id' => $apprentice->coach->id,
                'name' => $apprentice->coach->name,
            ],
            'trainer' => $apprentice->trainer === null ? null : [
                'id' => $apprentice->trainer->id,
                'name' => $apprentice->trainer->name,
            ],
        ];
    }

    /** Short label of the apprentice's section: "IT", "EC", or null if none. */
    private function track(User $apprentice): ?string
    {
        return match ($apprentice->apprenticeship?->name) {
            ApprenticeshipSeeder::IT => 'IT',
            ApprenticeshipSeeder::EC => 'EC',
            null => null,
            default => $apprentice->apprenticeship->name,
        };
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
