<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Local admin: who coaches which apprentice. `coach_id` is not mass assignable
 * (see ADR): it is only written here and in ApprenticeController::assign().
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
}
