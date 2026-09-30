<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Project;
use App\Models\User;

/**
 * Apprentices own their portfolio: only they add, edit or delete its projects;
 * supervisors get read-only access to the portfolios they follow
 * (role_permissions.md, Training Portfolio).
 */
class ProjectPolicy
{
    public function view(User $user, Project $project): bool
    {
        if ($project->user_id === $user->id) {
            return true;
        }

        return $user->hasPermissionTo(Permission::PortfolioViewSupervised->value)
            && $user->supervises($project->user);
    }

    public function create(User $user): bool
    {
        return $user->is_active
            && $user->hasPermissionTo(Permission::PortfolioManageOwn->value);
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
