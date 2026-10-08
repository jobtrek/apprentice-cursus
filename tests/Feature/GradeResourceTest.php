<?php

use App\Models\Domain;
use App\Models\DomainLink;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

const MODULES_PRO = 'Modules école professionnelle';
const IT_SKILLS = 'Compétences en informatique';

/**
 * The domain reached from the test root by following the given names,
 * creating the missing domains and links on the way.
 */
function domainAt(string ...$names): Domain
{
    $domain = test()->root;

    foreach ($names as $name) {
        $child = $domain->children()->where('name', $name)->first();

        if ($child === null) {
            $child = Domain::query()->create(['name' => $name]);
            DomainLink::query()->forceCreate(['parent_id' => $domain->id, 'child_id' => $child->id]);
        }

        $domain = $child;
    }

    return $domain;
}

beforeEach(function () {
    $this->root = Domain::query()->create(['name' => 'Note finale CFC']);
    $this->apprentice = User::factory()->create();
});

test('the dashboard exposes the ancestor path of each grade without the root', function () {
    $cg = makeGrade($this->apprentice, domainAt('Culture générale', 'Culture générale — Examen final'));
    $moduleGrade = makeGrade($this->apprentice, domainAt(IT_SKILLS, MODULES_PRO, 'Module 1'));
    domainAt('Travail pratique individuel (TPI)');

    $this->actingAs($this->apprentice)->get(route('grades.dashboard'))
        ->assertInertia(function (Assert $page) use ($cg, $moduleGrade) {
            $grades = collect($page->toArray()['props']['grades'])->keyBy('id');

            expect($grades[$cg->id]['path'])->toBe(['Culture générale']);
            expect($grades[$cg->id]['subject'])->toBe('Culture générale');
            expect($grades[$moduleGrade->id]['path'])->toBe([IT_SKILLS, MODULES_PRO]);
            expect($grades[$moduleGrade->id]['subject'])->toBe(MODULES_PRO);
        });
});

test('a grade carries its domain as node id and title, and the semester of its period', function () {
    $domain = domainAt('Culture générale', 'Culture générale — Examen final');
    $grade = makeGrade($this->apprentice, $domain);

    $this->actingAs($this->apprentice)->get(route('grades.dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('grades.0.node_id', $domain->id)
            ->where('grades.0.title', 'Culture générale — Examen final')
            ->where('grades.0.semester', $grade->apprenticeshipPeriod->semester));
});

test('a grade on an orphan domain has an empty path and subject', function () {
    makeGrade($this->apprentice);

    $this->actingAs($this->apprentice)->get(route('grades.dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('grades.0.path', [])
            ->where('grades.0.subject', ''));
});

test('the grade detail page exposes the same path', function () {
    $grade = makeGrade($this->apprentice, domainAt('Culture générale', 'Culture générale — Examen final'));

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
    makeGrade($this->apprentice, domainAt(IT_SKILLS, MODULES_PRO, 'Module 1'));
    $single = $countQueries();

    makeGrade($this->apprentice, domainAt('Culture générale', 'Culture générale — Examen final'));
    makeGrade($this->apprentice, domainAt('Travail pratique individuel (TPI)'));
    makeGrade($this->apprentice, domainAt(IT_SKILLS, MODULES_PRO, 'Module 2'));
    makeGrade($this->apprentice, domainAt('Compétences de base élargies', 'Mathématiques'));

    expect($countQueries())->toBe($single);
});
