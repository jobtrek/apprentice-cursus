<?php

namespace App\Policies;

use App\Models\Grade;
use App\Models\User;

/**
 * role_permissions.md — "Grade Submission & My Grade Record" and "Review by Trainer/Coach".
 */
class GradePolicy
{
    /**
     * The own grade record: only apprentices have one.
     */
    public function viewAny(User $user): bool
    {
        return $user->isApprentice();
    }

    /**
     * Own grade for apprentices, section for trainers, all for coaches (read-only).
     */
    public function view(User $user, Grade $grade): bool
    {
        return $user->canSeeApprentice($grade->user);
    }

    public function create(User $user): bool
    {
        return $user->isApprentice() && $user->is_active;
    }

    /**
     * Trainers and coaches never edit or delete an apprentice's grade.
     */
    public function update(User $user, Grade $grade): bool
    {
        return $user->is_active && $user->isApprentice() && $user->id === $grade->user_id;
    }

    public function delete(User $user, Grade $grade): bool
    {
        return $this->update($user, $grade);
    }
}
