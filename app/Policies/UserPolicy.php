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

    /** Only an active apprentice with no coach: taking one over from another coach is refused. */
    public function assignSelf(User $user, User $apprentice): bool
    {
        return $user->hasPermissionTo(Permission::CoachingAssignSelf->value)
            && $apprentice->hasRole(UserRole::Apprentice->value)
            && $apprentice->is_active
            && $apprentice->coach_id === null;
    }

    /** Assign or remove the coach of any apprentice (local admin only). */
    public function assignCoach(User $user, User $apprentice): bool
    {
        return $user->hasPermissionTo(Permission::SupervisionManage->value)
            && $apprentice->hasRole(UserRole::Apprentice->value);
    }
}
