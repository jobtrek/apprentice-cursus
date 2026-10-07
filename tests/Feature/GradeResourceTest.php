<?php

use App\Models\Apprenticeship;
use App\Models\EvaluationNode;
use App\Models\Grade;
use App\Models\User;
use Database\Seeders\ApprenticeshipSeeder;
use Database\Seeders\EvaluationTreeSeeder;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

const MODULES_PRO = 'Modules école professionnelle';
const IT_SKILLS = 'Compétences en informatique';

function itNode(string ...$names): EvaluationNode
{
    $node = test()->itRoot;

    foreach ($names as $name) {
        $node = $node->children()->where('name', $name)->sole();
    }

    return $node;
}

function gradeOn(User $apprentice, EvaluationNode $node, string $date = '2026-01-15'): Grade
{
    return Grade::query()->create([
        'user_id' => $apprentice->id,
        'evaluation_node_id' => $node->id,
        'value' => 5.0,
        'test_date' => $date,
        'semester' => 1,
    ]);
}

beforeEach(function () {
    $this->seed([ApprenticeshipSeeder::class, EvaluationTreeSeeder::class]);
    $this->itRoot = Apprenticeship::query()
        ->where('name', ApprenticeshipSeeder::IT)->sole()->evaluationTree()->sole();
    $this->apprentice = User::factory()->create();
});

test('the dashboard exposes the ancestor path of each grade without the root', function () {
    $cg = gradeOn($this->apprentice, itNode('Culture générale', 'Culture générale — Examen final'));
    $module = itNode(IT_SKILLS, MODULES_PRO)->children()->firstOrFail();
    $moduleGrade = gradeOn($this->apprentice, $module);

    $this->actingAs($this->apprentice)->get(route('grades.dashboard'))
        ->assertInertia(function (Assert $page) use ($cg, $moduleGrade) {
            $grades = collect($page->toArray()['props']['grades'])->keyBy('id');

            expect($grades[$cg->id]['path'])->toBe(['Culture générale']);
            expect($grades[$cg->id]['path'])->not->toContain('Travail pratique individuel (TPI)');
            expect($grades[$moduleGrade->id]['path'])->toBe([IT_SKILLS, MODULES_PRO]);
        });
});

test('a grade on an orphan node has an empty path and subject', function () {
    $grade = makeGrade($this->apprentice);

    $this->actingAs($this->apprentice)->get(route('grades.dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('grades.0.path', [])
            ->where('grades.0.subject', ''));
});

test('the grade detail page exposes the same path', function () {
    $grade = gradeOn($this->apprentice, itNode('Culture générale', 'Culture générale — Examen final'));

    $this->actingAs($this->apprentice)->get(route('grades.show', $grade))
        ->assertInertia(fn (Assert $page) => $page->where('grade.path', ['Culture générale']));
});

test('the dashboard query count does not depend on the number of grades', function () {
    $countQueries = function (): int {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($this->apprentice)->get(route('grades.dashboard'))->assertOk();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    };

    // Warm up the permission cache so it is not counted.
    $this->actingAs($this->apprentice)->get(route('grades.dashboard'))->assertOk();

    // Eager loading stops once a level is empty, so start from the deepest leaf.
    gradeOn($this->apprentice, itNode(IT_SKILLS, MODULES_PRO)->children()->firstOrFail());
    $single = $countQueries();

    gradeOn($this->apprentice, itNode('Culture générale', 'Culture générale — Examen final'));
    gradeOn($this->apprentice, itNode('Travail pratique individuel (TPI)'));
    gradeOn($this->apprentice, itNode(IT_SKILLS, MODULES_PRO)->children()->skip(1)->firstOrFail());
    gradeOn($this->apprentice, itNode('Compétences de base élargies', 'Mathématiques'));

    expect($countQueries())->toBe($single);
});

test('the coach profile gets the same evaluation tree as the apprentice gradebook', function () {
    $coach = User::factory()->coach()->create();
    $this->apprentice->forceFill([
        'apprenticeship_id' => Apprenticeship::query()->where('name', ApprenticeshipSeeder::IT)->sole()->id,
        'coach_id' => $coach->id,
    ])->save();

    $own = null;
    $this->actingAs($this->apprentice)->get(route('grades.dashboard'))
        ->assertInertia(function (Assert $page) use (&$own) {
            $own = $page->toArray()['props']['tree'];
        });

    $this->actingAs($coach)->get(route('apprentices.show', $this->apprentice))
        ->assertInertia(fn (Assert $page) => $page
            ->where('tree.root', $this->itRoot->id)
            ->where('tree', $own));
});
