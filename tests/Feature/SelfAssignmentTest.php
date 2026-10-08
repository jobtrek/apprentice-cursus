<?php

use App\Models\User;
use Database\Seeders\ApprenticeshipSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->it = section(ApprenticeshipSeeder::IT);
    $this->ec = section(ApprenticeshipSeeder::EC);
});

/** @return User a trainer of the given section */
function trainerOf(object $section): User
{
    $trainer = User::factory()->trainer()->create();
    $trainer->forceFill(['apprenticeship_context_id' => contextFor($section)->id])->save();

    return $trainer;
}

describe('coach', function () {
    test('assigns itself to an apprentice with no coach', function () {
        $coach = User::factory()->coach()->create();
        $apprentice = makeApprentice($this->it);

        $this->actingAs($coach)
            ->from(route('apprentisdashboard'))
            ->post(route('apprentices.assign', $apprentice))
            ->assertRedirect(route('apprentisdashboard'));

        expect($apprentice->fresh()->coach_id)->toBe($coach->id)
            ->and($apprentice->fresh()->trainer_id)->toBeNull()
            ->and($coach->can('view', $apprentice->fresh()))->toBeTrue();
    });

    test('cannot take over an apprentice who already has a coach', function () {
        $otherCoach = User::factory()->coach()->create();
        $apprentice = makeApprentice($this->it, $otherCoach);

        $this->actingAs(User::factory()->coach()->create())
            ->post(route('apprentices.assign', $apprentice))
            ->assertForbidden();

        expect($apprentice->fresh()->coach_id)->toBe($otherCoach->id);
    });

    test('cannot assign itself to a deactivated apprentice or a supervisor', function () {
        $coach = User::factory()->coach()->create();
        $inactive = makeApprentice($this->it);
        $inactive->forceFill(['is_active' => false])->save();

        $this->actingAs($coach)->post(route('apprentices.assign', $inactive))->assertForbidden();
        $this->actingAs($coach)->post(route('apprentices.assign', User::factory()->coach()->create()))->assertForbidden();
    });

    test('is offered every active apprentice without a coach, of both sections', function () {
        $coach = User::factory()->coach()->create();
        makeApprentice($this->it, $coach)->forceFill(['name' => 'Mine'])->save();
        makeApprentice($this->ec, User::factory()->coach()->create())->forceFill(['name' => 'Taken'])->save();
        $free = makeApprentice($this->ec);
        $free->forceFill(['name' => 'Free'])->save();

        $this->actingAs($coach)
            ->get(route('apprentisdashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('can.assignSelfAs', 'coach')
                ->where('assignable', [['id' => $free->id, 'name' => 'Free', 'track' => 'EC']]));
    });
});

describe('trainer', function () {
    test('assigns itself to an apprentice of its section with no trainer', function () {
        $trainer = trainerOf($this->it);
        $apprentice = makeApprentice($this->it);

        $this->actingAs($trainer)
            ->from(route('apprentisdashboard'))
            ->post(route('apprentices.assign', $apprentice))
            ->assertRedirect(route('apprentisdashboard'));

        expect($apprentice->fresh()->trainer_id)->toBe($trainer->id)
            ->and($apprentice->fresh()->coach_id)->toBeNull()
            ->and($trainer->can('view', $apprentice->fresh()))->toBeTrue();
    });

    test('cannot assign itself to an apprentice of another section', function () {
        $trainer = trainerOf($this->it);
        $apprentice = makeApprentice($this->ec);

        $this->actingAs($trainer)
            ->post(route('apprentices.assign', $apprentice))
            ->assertForbidden();

        expect($apprentice->fresh()->trainer_id)->toBeNull();
    });

    test('cannot take over an apprentice who already has a trainer', function () {
        $other = trainerOf($this->it);
        $apprentice = makeApprentice($this->it, trainer: $other);

        $this->actingAs(trainerOf($this->it))
            ->post(route('apprentices.assign', $apprentice))
            ->assertForbidden();

        expect($apprentice->fresh()->trainer_id)->toBe($other->id);
    });

    test('without a section cannot assign anyone', function () {
        $this->actingAs(User::factory()->trainer()->create())
            ->post(route('apprentices.assign', makeApprentice($this->it)))
            ->assertForbidden();
    });

    test('is offered only the apprentices of its section without a trainer', function () {
        $trainer = trainerOf($this->ec);
        makeApprentice($this->ec, trainer: $trainer);
        makeApprentice($this->ec, trainer: trainerOf($this->ec));
        makeApprentice($this->it);
        $free = makeApprentice($this->ec);
        $free->forceFill(['name' => 'Free'])->save();

        $this->actingAs($trainer)
            ->get(route('apprentisdashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('can.assignSelfAs', 'trainer')
                ->where('assignable', [['id' => $free->id, 'name' => 'Free', 'track' => 'EC']]));
    });
});

test('an apprentice cannot assign itself to anyone', function () {
    $apprentice = makeApprentice($this->it);

    $this->actingAs(makeApprentice($this->it))
        ->post(route('apprentices.assign', $apprentice))
        ->assertForbidden();
});
