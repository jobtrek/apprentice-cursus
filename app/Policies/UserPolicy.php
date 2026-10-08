<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Models\User;

class UserPolicy
{
    public function view(User $user, User $apprentice): bool
    {
        return $user->hasPermissionTo(Permission::ApprenticesViewList->value)
            && $user->supervises($apprentice);
    }

    /**
     * A coach takes an active apprentice with no coach; a trainer one of its own
     * section with no trainer. Taking one over from someone else is refused.
     */
    public function assignSelf(User $user, User $apprentice): bool
    {
        if (! $apprentice->hasRole(UserRole::Apprentice->value) || ! $apprentice->is_active) {
            return false;
        }

        return match ($user->selfAssignmentColumn()) {
            'coach_id' => $apprentice->coach_id === null,
            'trainer_id' => $apprentice->trainer_id === null
                && $user->apprenticeshipId() !== null
                && $apprentice->apprenticeshipId() === $user->apprenticeshipId(),
            default => false,
        };
    }

    /** Assign or remove the coach of any apprentice (local admin only). */
    public function assignCoach(User $user, User $apprentice): bool
    {
        return $user->hasPermissionTo(Permission::SupervisionManage->value)
            && $apprentice->hasRole(UserRole::Apprentice->value);
    }

    /** Assign or remove the trainer of any apprentice (local admin only). */
    public function assignTrainer(User $user, User $apprentice): bool
    {
        return $user->hasPermissionTo(Permission::SupervisionManage->value)
            && $apprentice->hasRole(UserRole::Apprentice->value);
    }
}
