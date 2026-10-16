<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Rows the app cannot run without (plans, roles, skills, grade trees).
 * Idempotent: the image runs it on every boot (Dockerfile entrypoint, via DatabaseSeeder).
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
