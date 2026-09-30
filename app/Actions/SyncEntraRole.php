<?php

namespace App\Actions;

use App\Enums\UserRole;
use App\Models\Apprenticeship;
use App\Models\User;
use App\Services\Microsoft\RoleAssignment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Writes a resolved Entra role onto a user. The single write path for role, section
 * and is_active, used at login and by EnsureAzureAccountIsActive.
 */
class SyncEntraRole
{
    public function apply(User $user, RoleAssignment $assignment): void
    {
        $apprenticeshipId = $assignment->apprenticeshipCode === null
            ? null
            : Apprenticeship::where('code', $assignment->apprenticeshipCode)->value('id');

        if ($assignment->apprenticeshipCode !== null && $apprenticeshipId === null) {
            throw new RuntimeException("Apprenticeship [{$assignment->apprenticeshipCode}] is missing; run the migrations.");
        }

        // Sector change of an apprentice with grades needs their confirmation before the
        // grades are deleted (role_permissions.md#sector-change), which is not built yet:
        // keep the current section rather than attach grades to the wrong tree.
        if (
            $user->exists
            && $user->isApprentice()
            && $assignment->role === UserRole::Apprentice
            && $user->apprenticeship_id !== null
            && $user->apprenticeship_id !== $apprenticeshipId
            && $user->grades()->exists()
        ) {
            Log::warning('Apprentice changed section in Entra but has grades; section kept.', [
                'user_id' => $user->id,
                'from' => $user->apprenticeship_id,
                'to' => $apprenticeshipId,
            ]);

            $apprenticeshipId = $user->apprenticeship_id;
        }

        $isEcApprentice = $assignment->role === UserRole::Apprentice
            && $assignment->apprenticeshipCode === Apprenticeship::EC;

        DB::transaction(function () use ($user, $assignment, $apprenticeshipId, $isEcApprentice) {
            if ($user->exists && $user->isCoach() && $assignment->role !== UserRole::Coach) {
                $this->releaseCoachees($user);
            }

            $user->forceFill([
                'role' => $assignment->role,
                'apprenticeship_id' => $apprenticeshipId,
                'is_mp' => $isEcApprentice ? $user->is_mp : null,
                'is_active' => true,
            ])->save();
        });
    }

    /**
     * The user no longer has a role in Entra: deactivate, never delete.
     */
    public function revoke(User $user): void
    {
        DB::transaction(function () use ($user) {
            if ($user->isCoach()) {
                $this->releaseCoachees($user);
            }

            $user->forceFill(['is_active' => false])->save();
        });
    }

    private function releaseCoachees(User $coach): void
    {
        User::where('coach_id', $coach->id)->update(['coach_id' => null]);
    }
}
