<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests cannot open an apprentice page', function () {
    $response = $this->get(route('apprentices.show', ['apprentice' => 1]));
    $response->assertRedirect(route('login'));
});

test('apprentices cannot access the apprentice pages', function (string $route, array $parameters) {
    $this->actingAs(User::factory()->create());

    $this->get(route($route, $parameters))->assertForbidden();
})->with([
    'list' => ['apprentisdashboard', []],
    'apprentice' => ['apprentices.show', ['apprentice' => 3]],
    'grade' => ['apprentices.grades.show', ['apprentice' => 3, 'grade' => 2]],
]);

test('coaches and trainers can open the apprentice list', function (string $state) {
    $this->actingAs(User::factory()->{$state}()->create());

    $this->get(route('apprentisdashboard'))->assertOk();
})->with(['coach', 'trainer']);

test('the apprentice page renders the apprentice grade book', function () {
    $this->actingAs(User::factory()->coach()->create());

    $response = $this->get(route('apprentices.show', ['apprentice' => 3]));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('ApprenticeShow', false)
        ->where('apprenticeId', 3)
    );
});

test('an apprentice grade opens the selected grade in the apprentice context', function () {
    $this->actingAs(User::factory()->trainer()->create());

    $response = $this->get(route('apprentices.grades.show', [
        'apprentice' => 3,
        'grade' => 2,
    ]));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('GradeDetails', false)
        ->where('apprenticeId', 3)
        ->where('gradeId', 2)
        ->has('comments')
    );
});

test('the own grade details pass the selected grade without apprentice context', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->get(route('grades.show', ['grade' => 2]));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('GradeDetails', false)
        ->where('gradeId', 2)
        ->missing('apprenticeId')
    );
});

test('non-numeric ids are not routed', function (string $url) {
    $this->actingAs(User::factory()->coach()->create());

    $this->get($url)->assertNotFound();
})->with(['/apprentices/abc', '/apprentices/3/grades/abc', '/grades/abc']);
