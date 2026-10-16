<?php

use App\Models\FormationPlan;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('the apprentice list carries grade stats for each followed apprentice', function () {
    $coach = User::factory()->coach()->create();
    $apprentice = makeApprentice(coach: $coach);
    $apprentice->forceFill(['name' => 'A'])->save();
    $withoutGrades = makeApprentice(coach: $coach);
    $withoutGrades->forceFill(['name' => 'B'])->save();
    makeGrade(makeApprentice(coach: User::factory()->coach()->create()));

    makeGrade($apprentice);
    makeGrade($apprentice)->forceFill(['value' => 3.5, 'test_date' => '2026-03-02'])->save();

    $this->actingAs($coach)
        ->get(route('apprentisdashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('ApprentisDashboard')
            ->has('apprentices', 2)
            ->where('apprentices.0.id', $apprentice->id)
            ->where('apprentices.0.stats', [
                'grades_count' => 2,
                'average' => 4.3,
                'last_grade_date' => '2026-03-02',
            ])
            ->where('apprentices.1.id', $withoutGrades->id)
            ->where('apprentices.1.stats', [
                'grades_count' => 0,
                'average' => null,
                'last_grade_date' => null,
            ]));
});

test('the supervisor home carries the same grade stats as the apprentice list', function () {
    $coach = User::factory()->coach()->create();
    $apprentice = makeApprentice(coach: $coach);
    $apprentice->forceFill(['name' => 'A'])->save();
    makeGrade($apprentice)->forceFill(['value' => 3.5, 'test_date' => '2026-03-02'])->save();
    makeApprentice();

    $this->actingAs($coach)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('apprentices', 1)
            ->where('apprentices.0.stats', [
                'grades_count' => 1,
                'average' => 3.5,
                'last_grade_date' => '2026-03-02',
            ])
            // The apprentice without a coach is one the coach could take on.
            ->where('assignableCount', 1));
});

test('the list derives the apprenticeship year from the grade dates and flags MP apprentices', function () {
    $coach = User::factory()->coach()->create();
    $third = makeApprentice(section(FormationPlan::EC_MP), $coach);
    $third->forceFill(['name' => 'A'])->save();
    // School years 2023-24 and 2025-26: the latest grade is in the third year.
    makeGrade($third)->forceFill(['test_date' => '2024-03-01'])->save();
    makeGrade($third)->forceFill(['test_date' => '2025-09-15'])->save();
    makeApprentice(section(FormationPlan::EC), $coach)->forceFill(['name' => 'B'])->save();

    $this->actingAs($coach)
        ->get(route('apprentisdashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('apprentices.0.year', 3)
            ->where('apprentices.0.isMp', true)
            ->where('apprentices.1.year', null)
            ->where('apprentices.1.isMp', false));
});

test('the supervisor home lists the latest grades of followed apprentices, newest first', function () {
    $coach = User::factory()->coach()->create();
    $apprentice = makeApprentice(coach: $coach);
    $older = makeGrade($apprentice);
    $newer = makeGrade($apprentice);
    $newer->forceFill(['value' => 3.5, 'test_date' => '2026-03-02'])->save();
    $newer->comments()->make(['body' => 'Bien'])->forceFill(['author_id' => $coach->id])->save();
    makeGrade(makeApprentice());

    $this->actingAs($coach)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('latestGrades', 2)
            ->where('latestGrades.0', [
                'id' => $newer->id,
                'apprentice_id' => $apprentice->id,
                'apprentice' => $apprentice->name,
                'title' => 'Test node',
                'value' => 3.5,
                'date' => '2026-03-02',
                'comments_count' => 1,
            ])
            ->where('latestGrades.1.id', $older->id)
            ->where('latestGrades.1.comments_count', 0));
});

test('the supervisor home counts grades per month over the last year, without gaps', function () {
    $this->travelTo('2026-10-13');
    $coach = User::factory()->coach()->create();
    $first = makeApprentice(coach: $coach);
    $second = makeApprentice(coach: $coach);
    makeGrade($first)->forceFill(['test_date' => '2026-10-01'])->save();
    makeGrade($first)->forceFill(['test_date' => '2026-10-05'])->save();
    makeGrade($second)->forceFill(['test_date' => '2026-10-07'])->save();
    makeGrade($first)->forceFill(['test_date' => '2025-11-03'])->save();
    // Before the 12-month window, and an apprentice the coach does not follow.
    makeGrade($first)->forceFill(['test_date' => '2025-10-31'])->save();
    makeGrade(makeApprentice())->forceFill(['test_date' => '2026-10-02'])->save();

    $this->actingAs($coach)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('activity', 12)
            ->where('activity.0', ['month' => '2025-11', 'grades' => 1, 'apprentices' => 1])
            ->where('activity.1', ['month' => '2025-12', 'grades' => 0, 'apprentices' => 0])
            ->where('activity.11', ['month' => '2026-10', 'grades' => 3, 'apprentices' => 2]));
});

test('apprentices get no supervisor feed on their home', function () {
    $apprentice = makeApprentice();
    makeGrade($apprentice);

    $this->actingAs($apprentice)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('latestGrades', [])
            ->where('activity', []));
});
