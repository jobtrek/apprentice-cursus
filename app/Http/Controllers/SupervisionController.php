<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Local admin: who coaches and who trains which apprentice. `coach_id` and
 * `trainer_id` are not mass assignable (see ADR): they are only written here
 * and in ApprenticeController::assign().
 */
class SupervisionController extends Controller
{
    /** Local admin: set, change or remove (null) the coach of an apprentice. */
    public function updateCoach(Request $request, User $apprentice): RedirectResponse
    {
        Gate::authorize('assignCoach', $apprentice);

        $validated = $request->validate([
            'coach_id' => [
                'present',
                'nullable',
                'integer',
                Rule::in(User::role(UserRole::Coach->value)->pluck('id')),
            ],
        ]);

        $apprentice->forceFill(['coach_id' => $validated['coach_id']])->save();

        return back();
    }

    /**
     * Local admin: set, change or remove (null) the trainer of an apprentice.
     * Only a trainer of the apprentice's section: another one would not
     * supervise them (see User::supervises()).
     */
    public function updateTrainer(Request $request, User $apprentice): RedirectResponse
    {
        Gate::authorize('assignTrainer', $apprentice);

        $validated = $request->validate([
            'trainer_id' => [
                'present',
                'nullable',
                'integer',
                Rule::in(
                    User::role(UserRole::Trainer->value)
                        ->inSection($apprentice->apprenticeshipId())
                        ->pluck('id'),
                ),
            ],
        ], [
            'trainer_id.in' => 'Ce formateur n\'est pas de la filière de l\'apprenti·e.',
        ]);

        $apprentice->forceFill(['trainer_id' => $validated['trainer_id']])->save();

        return back();
    }
}
