<?php

namespace App\Support;

use App\Enums\Permission as PermissionName;
use App\Enums\UserRole;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Idempotently creates every permission and role of the application and wires
 * them together. Shared by the migration that introduces Spatie as the source
 * of truth for roles and by the seeder, so a migrated DB is always usable.
 */
final class RolesAndPermissions
{
    private const GUARD = 'web';

    public static function sync(): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();

        foreach (PermissionName::cases() as $permission) {
            Permission::findOrCreate($permission->value, self::GUARD);
        }

        foreach (UserRole::cases() as $userRole) {
            Role::findOrCreate($userRole->value, self::GUARD)->syncPermissions(
                array_map(
                    fn (PermissionName $permission): string => $permission->value,
                    PermissionName::byRole()[$userRole->value],
                ),
            );
        }

        $registrar->forgetCachedPermissions();
    }
}
