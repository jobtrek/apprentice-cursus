<?php

namespace Database\Seeders;

use App\Models\Skill;
use Illuminate\Database\Seeder;

/**
 * Initial IT skills catalog; afterwards the Super-Admin maintains it
 * (role_permissions.md).
 */
class SkillSeeder extends Seeder
{
    public function run(): void
    {
        collect([
            'Développement web',
            'Bases de données',
            'Gestion de projet',
            'Sécurité informatique',
            'Cloud & infrastructure',
            'Travail en équipe',
            'Assurance qualité',
        ])->each(fn (string $name) => Skill::firstOrCreate(['name' => $name]));
    }
}
