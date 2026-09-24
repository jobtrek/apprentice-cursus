<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'derection.view',
            'trainer.view',
            'coach.view',
            'apprentice_dev.view',
            'apprentice_ec.view',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }



        $trainer = Role::firstOrCreate([
            'name' => 'trainer',
            'guard_name' => 'web',
        ]);

        $coach = Role::firstOrCreate([
            'name' => 'coach',
            'guard_name' => 'web',
        ]);

        $apprentice_dev = Role::firstOrCreate([
            'name' => 'apprentice_dev',
            'guard_name' => 'web',
        ]);

        $apprentice_ec = Role::firstOrCreate([
            'name' => 'apprentice_ec',
            'guard_name' => 'web',
        ]);



        $trainer->givePermissionTo([
            'derection.view',
            'trainer.view',
        ]);


        $coach->givePermissionTo([
            'derection.view',
            'coach.view',
        ]);

        $apprentice_dev->givePermissionTo([
            'apprentice_dev.view',
        ]);

        $apprentice_ec->givePermissionTo([
            'apprentice_ec.view',
        ]);
    }
}
