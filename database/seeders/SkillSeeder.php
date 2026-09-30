<?php

namespace Database\Seeders;

use App\Models\Skill;
use Illuminate\Database\Seeder;

/**
 * Seeds the IT skills catalog apprentices pick from for portfolio projects.
 * Same list as the demo data in resources/js/data/portfolio.json.
 */
class SkillSeeder extends Seeder
{
    private const SKILLS = [
        'Développement web',
        'Bases de données',
        'Gestion de projet',
        'Sécurité informatique',
        'Cloud & infrastructure',
        'Travail en équipe',
        'Assurance qualité',
    ];

    public function run(): void
    {
        foreach (self::SKILLS as $name) {
            Skill::query()->firstOrCreate(['name' => $name]);
        }
    }
}
