<?php

namespace Database\Seeders;

use App\Enums\Permission as PermissionName;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PermissionName::cases() as $permission) {
            Permission::findOrCreate($permission->value);
        }

        foreach (UserRole::cases() as $userRole) {
            $role = Role::findOrCreate($userRole->value);
            $role->syncPermissions(
                array_map(fn (PermissionName $p): string => $p->value, PermissionName::byRole()[$userRole->value]),
            );
        }

        User::query()->each(fn (User $user) => $user->syncRoles($user->role->value));
    }
}
