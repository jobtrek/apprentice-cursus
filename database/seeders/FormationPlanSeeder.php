<?php

namespace Database\Seeders;

use App\Models\FormationPlan;
use Illuminate\Database\Seeder;

/**
 * Seeds the formation plans the app supports: IT, EC and its MP variant.
 */
class FormationPlanSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([FormationPlan::IT, FormationPlan::EC, FormationPlan::EC_MP] as $name) {
            FormationPlan::query()->firstOrCreate(['name' => $name]);
        }
    }
}
