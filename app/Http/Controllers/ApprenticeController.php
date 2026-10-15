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
use App\Support\ApprenticeList;
use App\Support\Gradebook\GradebookTree;
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

        $canManage = $user->can(Permission::SupervisionManage->value);

        return Inertia::render('ApprentisDashboard', [
            'apprentices' => ApprenticeList::for($user),
            // Offered by the "Ajouter un apprenti" dialog.
            'assignable' => ApprenticeList::assignable($user),
            'coaches' => $canManage
                ? User::role(UserRole::Coach->value)->orderBy('name')->get(['id', 'name'])
                : [],
            'can' => [
                'assignSelf' => $user->canSelfAssign(),
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
            'stats' => ApprenticeList::statsFor($apprentice),
            'grades' => GradeResource::collection(
                $apprentice->grades()
                    ->withSemester()
                    ->with(GradeResource::RELATIONS)
                    // Lets the gradebook filter the commented grades.
                    ->withCount('comments')
                    ->orderBy('test_date')
                    ->orderBy('id')
                    ->get(),
            )->resolve(),
            // Same tree as the apprentice's own gradebook: the profile shows the real CFC averages.
            'tree' => GradebookTree::for($apprentice),
            'portfolio' => $portfolio,
        ]);
    }

    /**
     * The coach takes the apprentice on. The whereNull guard makes two coaches
     * assigning themselves at once safe: the slower one updates no row and is refused.
     */
    public function assign(Request $request, User $apprentice): RedirectResponse
    {
        $user = $request->user();
        // The local admin passes every Gate but is no coach: it uses the selects.
        abort_unless($user->canSelfAssign(), 403);
        Gate::authorize('assignSelf', $apprentice);

        $assigned = User::query()
            ->whereKey($apprentice->id)
            ->whereNull('coach_id')
            ->update(['coach_id' => $user->id]);

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
            'pdfUrl' => GradeController::fileUrl($grade),
            'comments' => CommentResource::forGrade($grade),
            'apprenticeId' => $apprentice->id,
            'apprentice' => (new ApprenticeResource($apprentice->load(ApprenticeResource::RELATIONS)))->resolve(),
            'stats' => ApprenticeList::statsFor($apprentice),
            'grade' => (new GradeResource($grade->load(GradeResource::RELATIONS)))->resolve(),
            'can' => [
                'comment' => request()->user()->can('comment', $grade),
            ],
        ]);
    }
}
