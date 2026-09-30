<?php

use App\Models\User;

test('each role reaches only its pages', function (string $route, array $allowed) {
    $users = [
        'apprentice' => User::factory()->apprentice()->create(),
        'trainer' => User::factory()->trainer()->create(),
        'coach' => User::factory()->coach()->create(),
    ];

    foreach ($users as $role => $user) {
        $this->actingAs($user)
            ->get(route($route))
            ->assertStatus(in_array($role, $allowed, true) ? 200 : 403);
    }
})->with([
    'home' => ['home', ['apprentice', 'trainer', 'coach']],
    'add a grade' => ['grades.create', ['apprentice']],
    'portfolio' => ['portfolio.index', ['apprentice']],
    'portfolio preview' => ['portfolio.preview', ['apprentice']],
    'new project' => ['portfolio.projects.create', ['apprentice']],
    'apprentices dashboard' => ['apprentisdashboard', ['trainer', 'coach']],
    'administration' => ['administration', ['trainer', 'coach']],
]);

test('the frontend receives the abilities of the role', function () {
    $this->actingAs(User::factory()->trainer()->create())
        ->get(route('home'))
        ->assertInertia(fn ($page) => $page
            ->where('auth.apprenticeship', 'it')
            ->where('auth.can', [
                'createGrade' => false,
                'viewPortfolio' => false,
                'createProject' => false,
                'viewApprentices' => true,
                'viewAdministration' => true,
            ]));
});
