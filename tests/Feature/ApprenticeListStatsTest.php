<?php

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
                'last_grade_date' => '02.03.2026',
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
                'last_grade_date' => '02.03.2026',
            ])
            // The apprentice without a coach is one the coach could take on.
            ->where('assignableCount', 1));
});

test('the list derives the apprenticeship year from the latest semester graded and flags MP apprentices', function () {
    $coach = User::factory()->coach()->create();
    $third = makeApprentice(coach: $coach);
    $third->forceFill(['name' => 'A', 'is_mp' => true])->save();
    makeGrade($third)->forceFill(['semester' => 2])->save();
    makeGrade($third)->forceFill(['semester' => 5])->save();
    makeApprentice(coach: $coach)->forceFill(['name' => 'B'])->save();

    $this->actingAs($coach)
        ->get(route('apprentisdashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('apprentices.0.year', 3)
            ->where('apprentices.0.isMp', true)
            ->where('apprentices.1.year', null)
            ->where('apprentices.1.isMp', false));
});
