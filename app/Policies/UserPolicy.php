<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;

/**
 * Viewing an apprentice's pages: their coach, or a supervisor of the same section.
 */
class UserPolicy
{
    public function view(User $user, User $apprentice): bool
    {
        return $user->hasPermissionTo(Permission::ApprenticesViewList->value)
            && $user->supervises($apprentice);
    }
}
