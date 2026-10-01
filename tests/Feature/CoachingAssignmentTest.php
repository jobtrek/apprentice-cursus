<?php

use App\Models\User;
use Database\Seeders\ApprenticeshipSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->it = section(ApprenticeshipSeeder::IT);
});

test('a coach assigns itself to an apprentice with no coach', function () {
    $coach = User::factory()->coach()->create();
    $apprentice = makeApprentice($this->it);

    $this->actingAs($coach)
        ->from(route('apprentisdashboard'))
        ->post(route('apprentices.assign', $apprentice))
        ->assertRedirect(route('apprentisdashboard'));

    expect($apprentice->fresh()->coach_id)->toBe($coach->id)
        ->and($coach->can('view', $apprentice->fresh()))->toBeTrue();
});

test('assigning to an apprentice who already has a coach is refused', function () {
    $otherCoach = User::factory()->coach()->create();
    $apprentice = makeApprentice($this->it, $otherCoach);

    $this->actingAs(User::factory()->coach()->create())
        ->post(route('apprentices.assign', $apprentice))
        ->assertForbidden();

    expect($apprentice->fresh()->coach_id)->toBe($otherCoach->id);
});

test('a trainer cannot assign itself', function () {
    $trainer = User::factory()->trainer()->create();
    $trainer->forceFill(['apprenticeship_id' => $this->it->id])->save();
    $apprentice = makeApprentice($this->it);

    $this->actingAs($trainer)
        ->post(route('apprentices.assign', $apprentice))
        ->assertForbidden();

    expect($apprentice->fresh()->coach_id)->toBeNull();
});

test('a coach cannot assign itself to a deactivated apprentice or a supervisor', function () {
    $coach = User::factory()->coach()->create();
    $inactive = makeApprentice($this->it);
    $inactive->forceFill(['is_active' => false])->save();

    $this->actingAs($coach)->post(route('apprentices.assign', $inactive))->assertForbidden();
    $this->actingAs($coach)->post(route('apprentices.assign', User::factory()->coach()->create()))->assertForbidden();
});

test('the list flags only unassigned apprentices as assignable', function () {
    $coach = User::factory()->coach()->create();
    $mine = makeApprentice($this->it, $coach);
    $free = makeApprentice($this->it);

    $this->actingAs($coach)
        ->get(route('apprentisdashboard'))
        ->assertInertia(function (Assert $page) use ($mine, $free) {
            $rows = collect($page->toArray()['props']['apprentices'])->keyBy('id');

            expect($rows[$mine->id]['canAssign'])->toBeFalse()
                ->and($rows[$free->id]['canAssign'])->toBeTrue();
        });
});
