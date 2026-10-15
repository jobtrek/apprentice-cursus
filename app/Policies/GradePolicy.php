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
        if ($user->hasPermissionTo(Permission::GradesViewOwn->value) && $grade->user_id === $user->id) {
            return true;
        }

        return $user->hasPermissionTo(Permission::GradesViewSupervised->value)
            && $grade->user !== null
            && $user->supervises($grade->user);
    }

    /** An apprentice edits their own grades only; supervisors read them. */
    public function update(User $user, Grade $grade): bool
    {
        return $user->hasPermissionTo(Permission::GradesCreate->value) && $grade->user_id === $user->id;
    }

    public function delete(User $user, Grade $grade): bool
    {
        return $this->update($user, $grade);
    }

    public function comment(User $user, Grade $grade): bool
    {
        return $user->hasPermissionTo(Permission::GradesComment->value)
            && $grade->user !== null
            && $user->supervises($grade->user)
            && $grade->user->is_active;
    }
}
