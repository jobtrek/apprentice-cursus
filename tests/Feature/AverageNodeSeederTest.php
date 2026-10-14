<?php

use App\Models\AverageNode;
use App\Models\FormationPlan;
use Database\Seeders\AverageNodeSeeder;
use Database\Seeders\FormationPlanSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Children of a node as `name => weight`.
 *
 * @return array<string, float>
 */
function childWeights(AverageNode $node): array
{
    return $node->children()->get()
        ->mapWithKeys(fn (AverageNode $child) => [$child->name => (float) $child->weight])
        ->all();
}

function childNamed(AverageNode $node, string $name): AverageNode
{
    return $node->children()->where('name', $name)->sole();
}

function plan(string $name): FormationPlan
{
    return FormationPlan::query()->where('name', $name)->sole();
}

function treeOf(string $plan): AverageNode
{
    return plan($plan)->rootNode() ?? throw new LogicException("No tree for [{$plan}].");
}

function seedReferential(): void
{
    test()->seed([FormationPlanSeeder::class, AverageNodeSeeder::class]);
}

test('each formation plan has its complete grade tree', function () {
    seedReferential();

    expect(childWeights(treeOf(FormationPlan::IT)))->toEqual([
        'Travail pratique individuel (TPI)' => 0.4,
        'Culture générale' => 0.2,
        'Compétences en informatique' => 0.3,
        'Compétences de base élargies' => 0.1,
    ]);

    expect(childWeights(treeOf(FormationPlan::EC)))->toEqual([
        'Note d\'expérience' => 0.4,
        'Travail pratique' => 0.3,
        'Connaissances professionnelles et culture générale' => 0.3,
    ]);
});

test('the IT modules come from modules.json, grouped by school', function () {
    seedReferential();

    $modules = collect(File::json(resource_path('js/data/modules.json')))->countBy('school');
    $itSkills = childNamed(treeOf(FormationPlan::IT), 'Compétences en informatique');

    expect(childWeights($itSkills))->toEqual([
        'Modules école professionnelle' => 0.8,
        'Modules cours interentreprises' => 0.2,
    ]);
    expect(childNamed($itSkills, 'Modules école professionnelle')->children()->count())->toBe($modules['EPSIC']);
    expect(childNamed($itSkills, 'Modules cours interentreprises')->children()->count())->toBe($modules['CIE']);
});

test('EC and EC MP share the tree except their own "Note d\'expérience" branch', function () {
    seedReferential();

    $experience = childNamed(treeOf(FormationPlan::EC), 'Note d\'expérience');
    $standard = childNamed($experience, 'Note d\'expérience — Standard');
    $mp = childNamed($experience, 'Note d\'expérience — Maturité professionnelle');

    expect(treeOf(FormationPlan::EC_MP)->id)->toBe(treeOf(FormationPlan::EC)->id);

    expect(childWeights($standard))->toEqual([
        'Enseignement des connaissances professionnelles et de la culture générale' => 0.5,
        'Cours interentreprises' => 0.25,
        'Formation à la pratique professionnelle' => 0.25,
    ]);
    expect(childWeights($mp))->toEqual([
        'Formation à la pratique professionnelle' => 0.5,
        'Cours interentreprises' => 0.5,
    ]);

    $ec = plan(FormationPlan::EC)->averageNodes()->pluck('average_nodes.id');
    $ecMp = plan(FormationPlan::EC_MP)->averageNodes()->pluck('average_nodes.id');

    expect($ec)->toContain($standard->id, ...$standard->children()->pluck('id'))
        ->and($ec)->not->toContain($mp->id)
        ->and($ecMp)->toContain($mp->id, ...$mp->children()->pluck('id'))
        ->and($ecMp)->not->toContain($standard->id)
        // A node has one weight in the tree: the MP leaves are their own nodes.
        ->and($ecMp)->not->toContain(childNamed($standard, 'Cours interentreprises')->id)
        ->and($ec->intersect($ecMp))->toContain($experience->id, treeOf(FormationPlan::EC)->id);
});

test('semester leaves round to half points, composites to tenths', function () {
    seedReferential();

    $basics = childNamed(treeOf(FormationPlan::IT), 'Compétences de base élargies');

    expect((float) $basics->rounding_step)->toBe(0.1)
        ->and((float) childNamed($basics, 'Mathématiques')->rounding_step)->toBe(0.5);
});

test('seeding twice changes nothing', function () {
    seedReferential();

    $count = fn () => [
        AverageNode::query()->count(),
        DB::table('average_node_formation_plan')->count(),
    ];
    $before = $count();

    seedReferential();

    expect($count())->toBe($before);
});

test('a node that only shares the root name is not mistaken for the tree', function () {
    $decoy = AverageNode::query()->create(['name' => AverageNodeSeeder::IT_ROOT]);

    seedReferential();

    $tree = treeOf(FormationPlan::IT);

    expect($tree->id)->not->toBe($decoy->id);
    expect($tree->children()->count())->toBe(4);
});

test('a node cannot be moved under one of its own descendants', function () {
    $root = AverageNode::query()->create(['name' => 'Root']);
    $child = AverageNode::query()->create(['name' => 'Child', 'parent_id' => $root->id, 'weight' => 1]);

    $root->parent_id = $child->id;

    expect(fn () => $root->save())->toThrow(LogicException::class, 'cycle');
});
