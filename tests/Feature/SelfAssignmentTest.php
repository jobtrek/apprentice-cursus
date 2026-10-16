<?php

use App\Models\FormationPlan;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->it = section(FormationPlan::IT);
    $this->ec = section(FormationPlan::EC);
});

describe('coach', function () {
    test('assigns itself to an apprentice with no coach', function () {
        $coach = User::factory()->coach()->create();
        $apprentice = makeApprentice($this->it);

        $this->actingAs($coach)
            ->from(route('apprentisdashboard'))
            ->post(route('apprentices.assign', $apprentice))
            ->assertRedirect(route('apprentisdashboard'));

        expect($apprentice->fresh()->coach_id)->toBe($coach->id)
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
                ->where('can.assignSelf', true)
                ->where('assignable', [['id' => $free->id, 'name' => 'Free', 'track' => 'EC']]));
    });
});

describe('trainer', function () {
    test('cannot assign itself: it follows the whole plan instead', function () {
        $trainer = makeTrainer($this->it);
        $apprentice = makeApprentice($this->it);

        $this->actingAs($trainer)
            ->post(route('apprentices.assign', $apprentice))
            ->assertForbidden();

        expect($trainer->can('view', $apprentice))->toBeTrue();
    });

    test('is offered no apprentice to add', function () {
        makeApprentice($this->it);

        $this->actingAs(makeTrainer($this->it))
            ->get(route('apprentisdashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('can.assignSelf', false)
                ->where('assignable', []));
    });

    test('is not offered the add button on the home page', function () {
        makeApprentice($this->it);

        $this->actingAs(makeTrainer($this->it))
            ->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('can.assignSelf', false)
                ->where('assignableCount', 0));
    });
});

test('an apprentice cannot assign itself to anyone', function () {
    $apprentice = makeApprentice($this->it);

    $this->actingAs(makeApprentice($this->it))
        ->post(route('apprentices.assign', $apprentice))
        ->assertForbidden();
});
