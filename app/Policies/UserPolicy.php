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
     * A coach takes an active apprentice with no coach; taking one over from
     * someone else is refused. Trainers follow whole plans, not single apprentices.
     */
    public function assignSelf(User $user, User $apprentice): bool
    {
        if (! $apprentice->hasRole(UserRole::Apprentice->value) || ! $apprentice->is_active) {
            return false;
        }

        return $user->selfAssignmentColumn() === 'coach_id' && $apprentice->coach_id === null;
    }

    /** Assign or remove the coach of any apprentice (local admin only). */
    public function assignCoach(User $user, User $apprentice): bool
    {
        return $user->hasPermissionTo(Permission::SupervisionManage->value)
            && $apprentice->hasRole(UserRole::Apprentice->value);
    }
}
