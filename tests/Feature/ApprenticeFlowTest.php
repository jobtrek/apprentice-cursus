<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests cannot open an apprentice page', function () {
    $response = $this->get(route('apprentices.show', ['apprentice' => 1]));
    $response->assertRedirect(route('login'));
});

test('the apprentice page renders the apprentice grade book', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->get(route('apprentices.show', ['apprentice' => 3]));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('ApprenticeShow', false)
        ->where('apprenticeId', 3)
    );
});

test('an apprentice grade opens the grade details in the apprentice context', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->get(route('apprentices.grades.show', [
        'apprentice' => 3,
        'grade' => 2,
    ]));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('GradeDetails', false)
        ->where('apprenticeId', 3)
        ->has('comments')
    );
});

test('the own grade details have no apprentice context', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->get(route('grades.show', ['grade' => 2]));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('GradeDetails', false)
        ->missing('apprenticeId')
    );
});

test('non-numeric apprentice ids are not routed', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/apprentices/abc')->assertNotFound();
});
