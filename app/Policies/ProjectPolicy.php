<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Project;
use App\Models\User;

/**
 * Apprentices own their portfolio: only they add, edit or delete its projects
 * (role_permissions.md, Training Portfolio).
 */
class ProjectPolicy
{
    public function create(User $user): bool
    {
        return $user->role === UserRole::Apprentice;
    }

    public function update(User $user, Project $project): bool
    {
        return $this->create($user) && $project->user_id === $user->id;
    }

    public function delete(User $user, Project $project): bool
    {
        return $this->update($user, $project);
    }
}
