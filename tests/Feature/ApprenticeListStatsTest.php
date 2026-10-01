<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('the apprentice list carries grade stats for supervised apprentices only', function () {
    $coach = User::factory()->coach()->create();
    $apprentice = makeApprentice(coach: $coach);
    $withoutGrades = makeApprentice(coach: $coach);
    $other = makeApprentice(coach: User::factory()->coach()->create());

    makeGrade($apprentice);
    makeGrade($apprentice)->forceFill(['value' => 3.5, 'test_date' => '2026-03-02'])->save();
    makeGrade($other);

    $this->actingAs($coach)
        ->get(route('apprentisdashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('ApprentisDashboard')
            ->has('stats', 2)
            ->where("stats.{$apprentice->id}", [
                'grades_count' => 2,
                'average' => 4.3,
                'last_grade_date' => '02.03.2026',
            ])
            ->where("stats.{$withoutGrades->id}", [
                'grades_count' => 0,
                'average' => null,
                'last_grade_date' => null,
            ])
            ->missing("stats.{$other->id}"));
});
