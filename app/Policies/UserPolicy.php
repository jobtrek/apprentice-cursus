<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;

class UserPolicy
{
    public function view(User $user, User $apprentice): bool
    {
        return $user->hasPermissionTo(Permission::ApprenticesViewList->value)
            && $user->supervises($apprentice);
    }
}
