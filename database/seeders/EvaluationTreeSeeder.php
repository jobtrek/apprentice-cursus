<?php

namespace Database\Seeders;

use App\Models\Apprenticeship;
use App\Models\ApprenticeshipContext;
use App\Models\Domain;
use App\Models\DomainLinkWeight;
use App\Models\Subject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use LogicException;

/**
 * Seeds the IT and EC grade trees as domains, shared links, and
 * apprenticeship-context-specific link weights.
 */
class EvaluationTreeSeeder extends Seeder
{
    private const COMPOSITE_ROUNDING = 0.1;

    private const SEMESTER_ROUNDING = 0.5;

    public const IT_ROOT = 'Note finale CFC — Informaticien·ne';

    public const EC_ROOT = 'Note finale CFC — Employé·e de commerce';

    /** @var array<string, Domain> */
    private array $domainsByKey = [];

    /** @var array<string, array<int, float>> */
    private array $weightsByContext = [];

    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedTree(
                ApprenticeshipSeeder::IT,
                fn (): array => $this->itTree(),
                ['standard' => false],
            );
            $this->seedTree(
                ApprenticeshipSeeder::EC,
                fn (): array => $this->ecTree(),
                ['standard' => false, 'mp' => true],
            );
        });
    }

    /**
     * @param  callable(): array<string, mixed>  $tree
     * @param  array<string, bool>  $contexts
     */
    private function seedTree(string $apprenticeshipName, callable $tree, array $contexts): void
    {
        $apprenticeship = Apprenticeship::query()
            ->where('name', $apprenticeshipName)
            ->firstOr(fn () => throw new LogicException(
                "Apprenticeship [{$apprenticeshipName}] not found: run ApprenticeshipSeeder first.",
            ));

        if ($apprenticeship->contexts()->count() === count($contexts)) {
            return;
        }

        $this->domainsByKey = [];
        $this->weightsByContext = array_fill_keys(array_keys($contexts), []);
        $root = $this->createDomain($tree());

        foreach ($contexts as $key => $isMp) {
            $context = ApprenticeshipContext::query()->updateOrCreate(
                ['apprenticeship_id' => $apprenticeship->id, 'is_mp' => $isMp],
                ['root_domain_id' => $root->id],
            );

            if ($this->weightsByContext[$key] === []) {
                continue;
            }

            foreach ($this->weightsByContext[$key] as $linkId => $weight) {
                DomainLinkWeight::query()->updateOrCreate(
                    [
                        'apprenticeship_context_id' => $context->id,
                        'domain_link_id' => $linkId,
                    ],
                    ['weight' => $weight],
                );
            }
        }
    }

    /**
     * @param  array<string, mixed>  $spec
     *
     * @phpstan-impure
     */
    private function createDomain(array $spec): Domain
    {
        $children = $spec['children'] ?? [];
        $key = $spec['key'] ?? $spec['name'];
        $domain = $this->domainsByKey[$key] ??= Domain::query()->create([
            'name' => $spec['name'],
            'rounding_step' => $spec['rounding']
                ?? ($children === []
                    ? (($spec['scope'] ?? null) === 'semester' ? self::SEMESTER_ROUNDING : null)
                    : self::COMPOSITE_ROUNDING),
        ]);

        if ($children === []) {
            $subject = Subject::query()->firstOrCreate(['name' => $spec['name']]);
            $domain->subjects()->syncWithoutDetaching([$subject->id]);

            return $domain;
        }

        foreach ($children as $childSpec) {
            $child = $this->createDomain($childSpec);
            $link = $domain->linkChild($child);

            foreach ($childSpec['weights'] ?? [] as $context => $weight) {
                $this->weightsByContext[$context][$link->id] = (float) $weight;
            }
        }

        return $domain;
    }

    /** @return array<string, mixed> */
    private function itTree(): array
    {
        $modules = $this->itModules();

        return [
            'name' => self::IT_ROOT,
            'children' => [
                [
                    'name' => 'Travail pratique individuel (TPI)',
                    'weights' => ['standard' => 0.40],
                    'children' => [
                        ['name' => 'TPI — Exécution et résultat du travail', 'weights' => ['standard' => 0.50]],
                        ['name' => 'TPI — Documentation', 'weights' => ['standard' => 0.20]],
                        ['name' => 'TPI — Présentation et entretien professionnel', 'weights' => ['standard' => 0.30]],
                    ],
                ],
                [
                    'name' => 'Culture générale',
                    'weights' => ['standard' => 0.20],
                    'children' => [
                        ['name' => 'Culture générale — Travail personnel d\'approfondissement (TPA)', 'weights' => ['standard' => 0.33]],
                        ['name' => 'Culture générale — Examen final', 'weights' => ['standard' => 0.33]],
                        ['name' => 'Culture générale — Note d\'expérience', 'weights' => ['standard' => 0.34], 'scope' => 'semester'],
                    ],
                ],
                [
                    'name' => 'Compétences en informatique',
                    'weights' => ['standard' => 0.30],
                    'children' => [
                        [
                            'name' => 'Modules école professionnelle',
                            'weights' => ['standard' => 0.80],
                            'children' => $modules['EPSIC'],
                        ],
                        [
                            'name' => 'Modules cours interentreprises',
                            'weights' => ['standard' => 0.20],
                            'children' => $modules['CIE'],
                        ],
                    ],
                ],
                [
                    'name' => 'Compétences de base élargies',
                    'weights' => ['standard' => 0.10],
                    'children' => [
                        ['name' => 'Mathématiques', 'weights' => ['standard' => 0.50], 'scope' => 'semester'],
                        ['name' => 'Anglais', 'weights' => ['standard' => 0.50], 'scope' => 'semester'],
                    ],
                ],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function ecTree(): array
    {
        return [
            'name' => self::EC_ROOT,
            'children' => [
                [
                    'name' => 'Note d\'expérience',
                    'weights' => ['standard' => 0.40, 'mp' => 0.40],
                    'children' => [
                        [
                            'name' => 'Enseignement des connaissances professionnelles et de la culture générale',
                            'weights' => ['standard' => 0.50],
                            'scope' => 'semester',
                        ],
                        [
                            'key' => 'ec-cie',
                            'name' => 'Cours interentreprises',
                            'weights' => ['standard' => 0.25, 'mp' => 0.50],
                        ],
                        [
                            'key' => 'ec-workplace',
                            'name' => 'Formation à la pratique professionnelle',
                            'weights' => ['standard' => 0.25, 'mp' => 0.50],
                            'scope' => 'semester',
                        ],
                    ],
                ],
                [
                    'name' => 'Travail pratique',
                    'weights' => ['standard' => 0.30, 'mp' => 0.30],
                    'children' => [
                        ['name' => 'Étude de cas spécifique à la branche', 'weights' => ['standard' => 1.0, 'mp' => 1.0]],
                    ],
                ],
                [
                    'name' => 'Connaissances professionnelles et culture générale',
                    'weights' => ['standard' => 0.30, 'mp' => 0.30],
                    'children' => [
                        ['name' => '1. Travail au sein de structures d\'activité et d\'organisation dynamiques', 'weights' => ['standard' => 0.20, 'mp' => 0.20]],
                        ['name' => '2. Interaction dans un milieu de travail interconnecté', 'weights' => ['standard' => 0.20, 'mp' => 0.20]],
                        ['name' => '3. Coordination des processus de travail en entreprise', 'weights' => ['standard' => 0.20, 'mp' => 0.20]],
                        ['name' => '4. Gestion des relations avec les clients et les fournisseurs', 'weights' => ['standard' => 0.20, 'mp' => 0.20]],
                        ['name' => '5. Utilisation des technologies numériques du monde du travail', 'weights' => ['standard' => 0.20, 'mp' => 0.20]],
                    ],
                ],
            ],
        ];
    }

    /** @return array{EPSIC: list<array<string, mixed>>, CIE: list<array<string, mixed>>} */
    private function itModules(): array
    {
        /** @var list<array{code: int, school: string, name: string}> $modules */
        $modules = File::json(resource_path('js/data/modules.json'));
        $epsic = [];
        $cie = [];
        $schoolCounts = array_count_values(array_column($modules, 'school'));
        $schoolIndexes = ['EPSIC' => 0, 'CIE' => 0];

        foreach ($modules as $module) {
            $schoolIndexes[$module['school']]++;
            $index = $schoolIndexes[$module['school']];
            $count = $schoolCounts[$module['school']];
            $weight = $index === $count
                ? round(1 - round(1 / $count, 2) * ($count - 1), 2)
                : round(1 / $count, 2);
            $leaf = [
                'name' => "{$module['code']} — {$module['name']}",
                'weights' => ['standard' => $weight],
            ];

            match ($module['school']) {
                'EPSIC' => $epsic[] = $leaf,
                'CIE' => $cie[] = $leaf,
                default => throw new LogicException("Unknown school [{$module['school']}] for module {$module['code']}."),
            };
        }

        return ['EPSIC' => $epsic, 'CIE' => $cie];
    }
}
