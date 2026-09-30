<?php

namespace App\Policies;

use App\Models\Apprenticeship;
use App\Models\User;

/**
 * Authorization on apprentices as people: the dashboard, coaching assignment and MP
 * status (role_permissions.md — "Review by Trainer/Coach", "Coaching Assignment",
 * "Apprentice Profile").
 */
class UserPolicy
{
    /**
     * The apprentices dashboard: trainers see their section, coaches everyone.
     */
    public function viewAny(User $user): bool
    {
        return $user->isTrainer() || $user->isCoach();
    }

    /**
     * An apprentice's grades, portfolio and profile, including archived (deactivated) ones.
     */
    public function view(User $user, User $apprentice): bool
    {
        return $user->canSeeApprentice($apprentice);
    }

    /**
     * A coach takes an active apprentice who has no coach yet. The write itself must
     * still be an atomic UPDATE … WHERE coach_id IS NULL.
     */
    public function assignCoach(User $user, User $apprentice): bool
    {
        return $user->isCoach()
            && $apprentice->isApprentice()
            && $apprentice->is_active
            && $apprentice->coach_id === null;
    }

    /**
     * A coach releases only their own, active apprentice.
     */
    public function unassignCoach(User $user, User $apprentice): bool
    {
        return $user->isCoach()
            && $apprentice->is_active
            && $apprentice->coach_id === $user->id;
    }

    /**
     * Only an EC apprentice declares their own MP status.
     */
    public function updateMpStatus(User $user, User $apprentice): bool
    {
        return $user->is($apprentice)
            && $user->isApprentice()
            && $user->apprenticeship?->code === Apprenticeship::EC;
    }
}
