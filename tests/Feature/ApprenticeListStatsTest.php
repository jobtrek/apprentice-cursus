<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('the apprentice list carries grade stats for supervised apprentices only', function () {
    $coach = User::factory()->coach()->create();
    $apprentice = makeApprentice(coach: $coach);
    $apprentice->forceFill(['name' => 'A'])->save();
    $withoutGrades = makeApprentice(coach: $coach);
    $withoutGrades->forceFill(['name' => 'B'])->save();
    $other = makeApprentice(coach: User::factory()->coach()->create());

    makeGrade($apprentice);
    makeGrade($apprentice)->forceFill(['value' => 3.5, 'test_date' => '2026-03-02'])->save();
    makeGrade($other);

    $this->actingAs($coach)
        ->get(route('apprentisdashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('ApprentisDashboard')
            ->has('apprentices', 2)
            ->where('apprentices.0.id', $apprentice->id)
            ->where('apprentices.0.coach', ['id' => $coach->id, 'name' => $coach->name])
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
