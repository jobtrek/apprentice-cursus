<?php

namespace Database\Seeders;

use App\Enums\AggregationType;
use App\Enums\EvaluationVariant;
use App\Enums\PeriodScope;
use App\Models\EvaluationNode;
use App\Models\Subject;
use App\Models\SubjectCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use LogicException;

/**
 * Seeds the IT and EC grade trees (docs/project-docs/grade_tree_*.md and
 * *-schema.mmd) into `evaluation_nodes` / `evaluation_node_connections`.
 *
 * Every leaf is a subject that grades are entered against; every composite
 * node is a weighted average of its children. Weights are percentages of
 * their parent and are meant to be normalized by their sum.
 *
 * Idempotent: a tree whose root already exists is left untouched.
 */
class EvaluationTreeSeeder extends Seeder
{
    /** Rounding of composite (domain and final) grades. */
    private const COMPOSITE_ROUNDING = 0.1;

    /** Rounding of semester grades (half points). */
    private const SEMESTER_ROUNDING = 0.5;

    public const IT_ROOT = 'Note finale CFC — Informaticien·ne';

    public const EC_ROOT = 'Note finale CFC — Employé·e de commerce';

    /**
     * Nodes declared with a `key`, reusable through `ref` (shared DAG children).
     *
     * @var array<string, EvaluationNode>
     */
    private array $nodesByKey = [];

    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedTree($this->itTree());
            $this->seedTree($this->ecTree());
        });
    }

    /**
     * @param  array<string, mixed>  $root
     */
    private function seedTree(array $root): void
    {
        if (EvaluationNode::query()->where('name', $root['name'])->exists()) {
            return;
        }

        $this->nodesByKey = [];
        $this->createNode($root);
    }

    /**
     * Creates a node and, recursively, its children.
     *
     * @param  array<string, mixed>  $spec
     */
    private function createNode(array $spec): EvaluationNode
    {
        /** @var list<array<string, mixed>> $children */
        $children = $spec['children'] ?? [];
        $isComposite = $children !== [];
        $scope = $spec['scope'] ?? PeriodScope::Cursus;

        $node = EvaluationNode::query()->create([
            'name' => $spec['name'],
            'subject_id' => $isComposite ? null : $this->createSubject($spec['name'])->id,
            'aggregation' => $isComposite ? AggregationType::WeightedAverage : null,
            'rounding_step' => $spec['rounding']
                ?? ($isComposite ? self::COMPOSITE_ROUNDING : ($scope === PeriodScope::Semester ? self::SEMESTER_ROUNDING : null)),
            'period_scope' => $scope,
            'variant' => $spec['variant'] ?? null,
        ]);

        if (isset($spec['key'])) {
            $this->nodesByKey[$spec['key']] = $node;
        }

        foreach ($children as $childSpec) {
            $child = isset($childSpec['ref'])
                ? $this->nodesByKey[$childSpec['ref']] ?? throw new LogicException("Unknown node ref [{$childSpec['ref']}].")
                : $this->createNode($childSpec);

            $node->addChild($child, (float) $childSpec['weight']);
        }

        return $node;
    }

    /**
     * A leaf's displayed name lives on its subject category (see ADR).
     */
    private function createSubject(string $name): Subject
    {
        $category = SubjectCategory::query()->firstOrCreate(['name' => $name]);

        return Subject::query()->create(['subject_category_id' => $category->id]);
    }

    /**
     * Informaticienne/Informaticien CFC — docs/project-docs/IT-schema.mmd.
     *
     * @return array<string, mixed>
     */
    private function itTree(): array
    {
        $modules = $this->itModules();

        return [
            'name' => self::IT_ROOT,
            'children' => [
                [
                    'name' => 'Travail pratique individuel (TPI)',
                    'weight' => 40,
                    'children' => [
                        ['name' => 'TPI — Exécution et résultat du travail', 'weight' => 50],
                        ['name' => 'TPI — Documentation', 'weight' => 20],
                        ['name' => 'TPI — Présentation et entretien professionnel', 'weight' => 30],
                    ],
                ],
                [
                    'name' => 'Culture générale',
                    'weight' => 20,
                    'children' => [
                        ['name' => 'Culture générale — Travail personnel d\'approfondissement (TPA)', 'weight' => 33.33],
                        ['name' => 'Culture générale — Examen final', 'weight' => 33.33],
                        ['name' => 'Culture générale — Note d\'expérience', 'weight' => 33.34, 'scope' => PeriodScope::Semester],
                    ],
                ],
                [
                    'name' => 'Compétences en informatique',
                    'weight' => 30,
                    'children' => [
                        [
                            'name' => 'Modules école professionnelle',
                            'weight' => 80,
                            'children' => $modules['EPSIC'],
                        ],
                        [
                            'name' => 'Modules cours interentreprises',
                            'weight' => 20,
                            'children' => $modules['CIE'],
                        ],
                    ],
                ],
                [
                    'name' => 'Compétences de base élargies',
                    'weight' => 10,
                    'children' => [
                        ['name' => 'Mathématiques', 'weight' => 50, 'scope' => PeriodScope::Semester],
                        ['name' => 'Anglais', 'weight' => 50, 'scope' => PeriodScope::Semester],
                    ],
                ],
            ],
        ];
    }

    /**
     * Employée/Employé de commerce CFC — docs/project-docs/EC-schema.mmd.
     * The MP variant is a sibling of the standard one (see ADR), sharing the
     * CIE and workplace-training leaves with different weights.
     *
     * @return array<string, mixed>
     */
    private function ecTree(): array
    {
        return [
            'name' => self::EC_ROOT,
            'children' => [
                [
                    'name' => 'Note d\'expérience',
                    'weight' => 40,
                    'children' => [
                        [
                            'name' => 'Note d\'expérience — Standard',
                            'weight' => 100,
                            'variant' => EvaluationVariant::Standard,
                            'children' => [
                                [
                                    'name' => 'Enseignement des connaissances professionnelles et de la culture générale',
                                    'weight' => 50,
                                    'scope' => PeriodScope::Semester,
                                ],
                                ['key' => 'ec-cie', 'name' => 'Cours interentreprises', 'weight' => 25],
                                [
                                    'key' => 'ec-workplace',
                                    'name' => 'Formation à la pratique professionnelle',
                                    'weight' => 25,
                                    'scope' => PeriodScope::Semester,
                                ],
                            ],
                        ],
                        [
                            'name' => 'Note d\'expérience — Maturité professionnelle',
                            'weight' => 100,
                            'variant' => EvaluationVariant::Mp,
                            'children' => [
                                ['ref' => 'ec-workplace', 'weight' => 50],
                                ['ref' => 'ec-cie', 'weight' => 50],
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'Travail pratique',
                    'weight' => 30,
                    'children' => [
                        ['name' => 'Étude de cas spécifique à la branche', 'weight' => 100],
                    ],
                ],
                [
                    'name' => 'Connaissances professionnelles et culture générale',
                    'weight' => 30,
                    'children' => [
                        ['name' => '1. Travail au sein de structures d\'activité et d\'organisation dynamiques', 'weight' => 20],
                        ['name' => '2. Interaction dans un milieu de travail interconnecté', 'weight' => 20],
                        ['name' => '3. Coordination des processus de travail en entreprise', 'weight' => 20],
                        ['name' => '4. Gestion des relations avec les clients et les fournisseurs', 'weight' => 20],
                        ['name' => '5. Utilisation des technologies numériques du monde du travail', 'weight' => 20],
                    ],
                ],
            ],
        ];
    }

    /**
     * IT modules grouped by school, from the list the grade form already uses.
     * Each module is a leaf with an equal weight inside its school.
     *
     * @return array{EPSIC: list<array<string, mixed>>, CIE: list<array<string, mixed>>}
     */
    private function itModules(): array
    {
        /** @var list<array{code: int, school: string, name: string}> $modules */
        $modules = File::json(resource_path('js/data/modules.json'));

        $epsic = [];
        $cie = [];

        foreach ($modules as $module) {
            $leaf = [
                'name' => "{$module['code']} — {$module['name']}",
                'weight' => 1,
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
