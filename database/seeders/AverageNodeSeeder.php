<?php

namespace Database\Seeders;

use App\Models\AverageNode;
use App\Models\FormationPlan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use LogicException;

/**
 * Seeds the IT and EC grade trees (docs/project-docs/grade_tree_*.md and
 * *-schema.mmd) into `average_nodes`, and links every node to the formation
 * plans that use it (`average_node_formation_plan`).
 *
 * Every leaf is a subject that grades are entered against; every other node is a
 * weighted average of its children. Weights below are percentages of their parent,
 * stored as fractions; the gradebook normalizes them by their sum.
 *
 * A node goes to every plan of its section, unless it (or an ancestor) is limited
 * with `plan`: that is how EC and EC MP share the whole tree except their own
 * "Note d'expérience" branch. A section whose first plan already has nodes is left
 * untouched (FormationPlanSeeder must run first). Node names are never used to find
 * an existing tree.
 */
class AverageNodeSeeder extends Seeder
{
    /** Rounding of composite (domain and final) grades. */
    private const COMPOSITE_ROUNDING = 0.1;

    /** Rounding of semester grades (half points). */
    private const SEMESTER_ROUNDING = 0.5;

    public const IT_ROOT = 'Note finale CFC — Informaticien·ne';

    public const EC_ROOT = 'Note finale CFC — Employé·e de commerce';

    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedTree([FormationPlan::IT], $this->itTree());
            $this->seedTree([FormationPlan::EC, FormationPlan::EC_MP], $this->ecTree());
        });
    }

    /**
     * @param  list<string>  $planNames
     * @param  array<string, mixed>  $tree
     */
    private function seedTree(array $planNames, array $tree): void
    {
        $plans = [];

        foreach ($planNames as $name) {
            $plans[$name] = FormationPlan::query()
                ->where('name', $name)
                ->firstOr(fn () => throw new LogicException(
                    "Formation plan [{$name}] not found: run FormationPlanSeeder first.",
                ));
        }

        if ($plans[$planNames[0]]->averageNodes()->exists()) {
            return;
        }

        $this->createNode($tree, null, $plans);
    }

    /**
     * Creates a node under $parent, links it to $plans and, recursively, creates its children.
     *
     * @param  array<string, mixed>  $spec
     * @param  array<string, FormationPlan>  $plans
     */
    private function createNode(array $spec, ?AverageNode $parent, array $plans): AverageNode
    {
        /** @var list<array<string, mixed>> $children */
        $children = $spec['children'] ?? [];

        if (isset($spec['plan'])) {
            $plans = array_intersect_key($plans, [$spec['plan'] => true]);
        }

        $node = AverageNode::query()->create([
            'parent_id' => $parent?->id,
            'name' => $spec['name'],
            'rounding_step' => $spec['rounding'] ?? ($children !== [] ? self::COMPOSITE_ROUNDING : null),
            'weight' => $parent === null ? null : round((float) $spec['weight'] / 100, 2),
        ]);

        $node->formationPlans()->attach(array_map(fn (FormationPlan $plan): int => $plan->id, array_values($plans)));

        foreach ($children as $childSpec) {
            $this->createNode($childSpec, $node, $plans);
        }

        return $node;
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
                        ['name' => 'Culture générale — Note d\'expérience', 'weight' => 33.34, 'rounding' => self::SEMESTER_ROUNDING],
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
                        ['name' => 'Mathématiques', 'weight' => 50, 'rounding' => self::SEMESTER_ROUNDING],
                        ['name' => 'Anglais', 'weight' => 50, 'rounding' => self::SEMESTER_ROUNDING],
                    ],
                ],
            ],
        ];
    }

    /**
     * Employée/Employé de commerce CFC — docs/project-docs/EC-schema.mmd.
     * Each "Note d'expérience" variant belongs to one plan only. A node has a single
     * weight in the tree, so the CIE and workplace-training leaves exist once per variant.
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
                            'plan' => FormationPlan::EC,
                            'children' => [
                                [
                                    'name' => 'Enseignement des connaissances professionnelles et de la culture générale',
                                    'weight' => 50,
                                    'rounding' => self::SEMESTER_ROUNDING,
                                ],
                                ['name' => 'Cours interentreprises', 'weight' => 25],
                                [
                                    'name' => 'Formation à la pratique professionnelle',
                                    'weight' => 25,
                                    'rounding' => self::SEMESTER_ROUNDING,
                                ],
                            ],
                        ],
                        [
                            'name' => 'Note d\'expérience — Maturité professionnelle',
                            'weight' => 100,
                            'plan' => FormationPlan::EC_MP,
                            'children' => [
                                [
                                    'name' => 'Formation à la pratique professionnelle',
                                    'weight' => 50,
                                    'rounding' => self::SEMESTER_ROUNDING,
                                ],
                                ['name' => 'Cours interentreprises', 'weight' => 50],
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
            $leaf = ['name' => "{$module['code']} — {$module['name']}"];

            match ($module['school']) {
                'EPSIC' => $epsic[] = $leaf,
                'CIE' => $cie[] = $leaf,
                default => throw new LogicException("Unknown school [{$module['school']}] for module {$module['code']}."),
            };
        }

        return ['EPSIC' => $this->equalWeights($epsic), 'CIE' => $this->equalWeights($cie)];
    }

    /**
     * Equal weights inside a school. Two decimals cannot always split 100 % exactly
     * (1/16 is stored as 0.06), which the normalization by the sum absorbs.
     *
     * @param  list<array{name: string}>  $leaves
     * @return list<array<string, mixed>>
     */
    private function equalWeights(array $leaves): array
    {
        return array_map(
            fn (array $leaf): array => [...$leaf, 'weight' => 100 / count($leaves)],
            $leaves,
        );
    }
}
