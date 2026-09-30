<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

/**
 * role_permissions.md — "Training Portfolio".
 */
class ProjectPolicy
{
    /**
     * The own portfolio: only apprentices have one. Trainers and coaches reach an
     * apprentice's portfolio through UserPolicy::view.
     */
    public function viewAny(User $user): bool
    {
        return $user->isApprentice();
    }

    public function view(User $user, Project $project): bool
    {
        return $user->canSeeApprentice($project->user);
    }

    public function create(User $user): bool
    {
        return $user->isApprentice() && $user->is_active;
    }

    public function update(User $user, Project $project): bool
    {
        return $user->is_active && $user->isApprentice() && $user->id === $project->user_id;
    }

    public function delete(User $user, Project $project): bool
    {
        return $this->update($user, $project);
    }
}
