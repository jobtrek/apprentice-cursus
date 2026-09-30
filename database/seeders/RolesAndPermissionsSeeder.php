<?php

namespace Database\Seeders;

use App\Support\RolesAndPermissions;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        RolesAndPermissions::sync();
    }
}
