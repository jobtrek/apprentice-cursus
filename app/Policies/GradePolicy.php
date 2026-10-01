<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Grade;
use App\Models\User;

class GradePolicy
{
    public function create(User $user): bool
    {
        return $user->hasPermissionTo(Permission::GradesCreate->value);
    }

    public function view(User $user, Grade $grade): bool
    {
        if ($user->hasPermissionTo(Permission::GradesViewOwn->value) && $grade->apprentice_id === $user->id) {
            return true;
        }

        return $user->hasPermissionTo(Permission::GradesViewSupervised->value)
            && $user->supervises($grade->apprentice);
    }

    public function comment(User $user, Grade $grade): bool
    {
        return $user->hasPermissionTo(Permission::GradesComment->value)
            && $user->supervises($grade->apprentice)
            && $grade->apprentice->is_active;
    }
}
