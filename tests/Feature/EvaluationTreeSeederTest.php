<?php

use App\Enums\EvaluationVariant;
use App\Enums\PeriodScope;
use App\Models\Apprenticeship;
use App\Models\EvaluationNode;
use App\Models\Subject;
use Database\Seeders\ApprenticeshipSeeder;
use Database\Seeders\EvaluationTreeSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Children of a node as `name => weight`.
 *
 * @return array<string, float>
 */
function childWeights(EvaluationNode $node): array
{
    return $node->children()->get()
        ->mapWithKeys(fn (EvaluationNode $child) => [$child->name => (float) $child->pivot->weight])
        ->all();
}

function childNamed(EvaluationNode $node, string $name): EvaluationNode
{
    return $node->children()->where('name', $name)->sole();
}

function treeOf(string $apprenticeship): EvaluationNode
{
    return Apprenticeship::query()->where('name', $apprenticeship)->sole()->evaluationTree()->sole();
}

function seedReferential(): void
{
    test()->seed([ApprenticeshipSeeder::class, EvaluationTreeSeeder::class]);
}

test('each apprenticeship is linked to its complete grade tree', function () {
    seedReferential();

    expect(childWeights(treeOf(ApprenticeshipSeeder::IT)))->toEqual([
        'Travail pratique individuel (TPI)' => 40.0,
        'Culture générale' => 20.0,
        'Compétences en informatique' => 30.0,
        'Compétences de base élargies' => 10.0,
    ]);

    expect(childWeights(treeOf(ApprenticeshipSeeder::EC)))->toEqual([
        'Note d\'expérience' => 40.0,
        'Travail pratique' => 30.0,
        'Connaissances professionnelles et culture générale' => 30.0,
    ]);
});

test('the IT modules come from modules.json, grouped by school', function () {
    seedReferential();

    $modules = collect(File::json(resource_path('js/data/modules.json')))->countBy('school');
    $itSkills = childNamed(treeOf(ApprenticeshipSeeder::IT), 'Compétences en informatique');

    expect(childWeights($itSkills))->toEqual([
        'Modules école professionnelle' => 80.0,
        'Modules cours interentreprises' => 20.0,
    ]);
    expect(childNamed($itSkills, 'Modules école professionnelle')->children()->count())->toBe($modules['EPSIC']);
    expect(childNamed($itSkills, 'Modules cours interentreprises')->children()->count())->toBe($modules['CIE']);
});

test('the EC MP variant is a sibling sharing the CIE and workplace leaves', function () {
    seedReferential();

    $experience = childNamed(treeOf(ApprenticeshipSeeder::EC), 'Note d\'expérience');
    $standard = childNamed($experience, 'Note d\'expérience — Standard');
    $mp = childNamed($experience, 'Note d\'expérience — Maturité professionnelle');

    expect($standard->variant)->toBe(EvaluationVariant::Standard);
    expect($mp->variant)->toBe(EvaluationVariant::Mp);

    expect(childWeights($standard))->toEqual([
        'Enseignement des connaissances professionnelles et de la culture générale' => 50.0,
        'Cours interentreprises' => 25.0,
        'Formation à la pratique professionnelle' => 25.0,
    ]);
    expect(childWeights($mp))->toEqual([
        'Formation à la pratique professionnelle' => 50.0,
        'Cours interentreprises' => 50.0,
    ]);

    foreach (['Cours interentreprises', 'Formation à la pratique professionnelle'] as $leaf) {
        expect(childNamed($mp, $leaf)->id)->toBe(childNamed($standard, $leaf)->id);
    }
});

test('leaves carry a subject and composites do not', function () {
    seedReferential();

    EvaluationNode::query()->each(function (EvaluationNode $node) {
        expect($node->subject_id !== null)->toBe($node->isLeaf(), $node->name);
    });

    expect(childNamed(treeOf(ApprenticeshipSeeder::IT), 'Compétences de base élargies')
        ->children()->where('name', 'Mathématiques')->sole()->period_scope)
        ->toBe(PeriodScope::Semester);
});

test('seeding twice changes nothing', function () {
    seedReferential();

    $count = fn () => [
        EvaluationNode::query()->count(),
        DB::table('evaluation_node_connections')->count(),
        Subject::query()->count(),
        Apprenticeship::query()->pluck('evaluation_node_id', 'name')->all(),
    ];
    $before = $count();

    seedReferential();

    expect($count())->toBe($before);
});

test('a node that only shares the root name is not mistaken for the tree', function () {
    $decoy = EvaluationNode::query()->create([
        'name' => EvaluationTreeSeeder::IT_ROOT,
        'period_scope' => PeriodScope::Cursus,
    ]);

    seedReferential();

    $tree = treeOf(ApprenticeshipSeeder::IT);

    expect($tree->id)->not->toBe($decoy->id);
    expect($tree->children()->count())->toBe(4);
});
