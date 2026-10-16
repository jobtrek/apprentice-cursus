<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Rows the app cannot run without (plans, roles, skills, grade trees).
 * Idempotent: production runs it on every boot (compose.prod.yaml).
 */
class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            FormationPlanSeeder::class,
            RolesAndPermissionsSeeder::class,
            SkillSeeder::class,
            AverageNodeSeeder::class,
        ]);
    }
}
