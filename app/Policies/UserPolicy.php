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

    /** A coach takes an active apprentice that has no coach yet. */
    public function assignSelfAsCoach(User $user, User $apprentice): bool
    {
        return $user->can(Permission::CoachingAssignSelf->value)
            && $apprentice->hasRole(UserRole::Apprentice->value)
            && $apprentice->is_active
            && $apprentice->coach_id === null;
    }

    /** A trainer takes an active apprentice of their own section that has no trainer yet. */
    public function assignSelfAsTrainer(User $user, User $apprentice): bool
    {
        return $user->can(Permission::TrainingAssignSelf->value)
            && $apprentice->hasRole(UserRole::Apprentice->value)
            && $apprentice->is_active
            && $apprentice->trainer_id === null
            && $user->apprenticeship_id !== null
            && $apprentice->apprenticeship_id === $user->apprenticeship_id;
    }

    /** Assign or remove the trainer of any apprentice (local admin only). */
    public function assignTrainer(User $user, User $apprentice): bool
    {
        return $user->can(Permission::SupervisionManage->value)
            && $apprentice->hasRole(UserRole::Apprentice->value);
    }

    /** Assign or remove the coach of any apprentice (local admin only). */
    public function assignCoach(User $user, User $apprentice): bool
    {
        return $user->can(Permission::SupervisionManage->value)
            && $apprentice->hasRole(UserRole::Apprentice->value);
    }
}
