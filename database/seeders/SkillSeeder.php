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
        // Développement web
        'Développement front-end',
        'Développement back-end',
        'Développement full-stack',
        'HTML & CSS',
        'JavaScript / TypeScript',
        'Responsive design',
        'Développement d’API REST',

        // Langages et frameworks
        'PHP',
        'Laravel',
        'Python',
        'Java',
        'JavaScript/TypeScript',
        'React',
        'Vue.js',
        'Node.js',

        // Bases de données
        'Modélisation de données',
        'SQL',
        'Conception de bases de données',
        'Gestion des migrations',
        'Optimisation des requêtes SQL',

        // Outils et collaboration
        'Git & GitHub / GitLab',
        'Gestion des branches Git',
        'Code review',
        'Travail en équipe',
        'Gestion de projet Agile / Scrum',
        'Documentation technique',

        // Qualité et sécurité
        'Tests unitaires',
        'Tests fonctionnels',
        'Débogage',
        'Refactorisation du code',
        'Sécurité des applications',
        'Assurance qualité logicielle',

        // Architecture et déploiement
        'Programmation orientée objet (POO)',
        'Architecture logicielle',
        'Principes SOLID',
        'Docker',
        'CI/CD',
        'Déploiement d’applications',

        // Conception
        'UI/UX design',
        'Prototypage',
        'Accessibilité web',
    ];

    public function run(): void
    {
        foreach (self::SKILLS as $name) {
            Skill::query()->firstOrCreate(['name' => $name]);
        }
    }
}
