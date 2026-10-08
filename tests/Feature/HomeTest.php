<?php

use App\Models\User;
use Database\Seeders\ApprenticeshipSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('home'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the home page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('home'));
    $response->assertOk();
});

test('the apprentice profile carries the section and the variant of its context', function (bool $mp, string $variant) {
    $section = section(ApprenticeshipSeeder::EC);
    $apprentice = makeApprentice();
    $apprentice->forceFill(['apprenticeship_context_id' => contextFor($section, $mp)->id])->save();

    $this->actingAs($apprentice)->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('profile.track', 'EC')
            ->where('profile.variant', $variant));
})->with([
    'standard context' => [false, 'standard'],
    'MP context' => [true, 'mp'],
]);

test('an apprentice without a context has no section and the standard variant', function () {
    $this->actingAs(makeApprentice())->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('profile.track', null)
            ->where('profile.variant', 'standard'));
});
