<?php

use App\Models\FormationPlan;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Inertia\Testing\AssertableInertia as Assert;

test('a coach cannot pick another coach for an apprentice', function () {
    $apprentice = makeApprentice();

    $this->actingAs(User::factory()->coach()->create())
        ->put(route('apprentices.coach.update', $apprentice), ['coach_id' => null])
        ->assertForbidden();
});

describe('local admin', function () {
    beforeEach(function () {
        app()->detectEnvironment(fn () => 'local');
        // Outside the `testing` environment Laravel checks the request origin / CSRF again.
        $this->withoutMiddleware(PreventRequestForgery::class);
    });

    test('sees every apprentice and the coaches to pick from', function () {
        $admin = User::factory()->admin()->create();
        $coach = User::factory()->coach()->create();
        makeApprentice(coach: $coach);
        makeApprentice();

        $this->actingAs($admin)
            ->get(route('apprentisdashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('apprentices', 2)
                ->where('coaches', [['id' => $coach->id, 'name' => $coach->name]])
                ->where('can.manageSupervision', true)
                // The local admin uses the selects, not the "Ajouter" dialog.
                ->where('can.assignSelfAs', null));
    });

    test('assigns, changes and removes the coach of an apprentice', function () {
        $admin = User::factory()->admin()->create();
        $first = User::factory()->coach()->create();
        $second = User::factory()->coach()->create();
        $apprentice = makeApprentice();

        $this->actingAs($admin)->put(route('apprentices.coach.update', $apprentice), ['coach_id' => $first->id])->assertRedirect();
        expect($apprentice->fresh()->coach_id)->toBe($first->id);

        $this->actingAs($admin)->put(route('apprentices.coach.update', $apprentice), ['coach_id' => $second->id])->assertRedirect();
        expect($apprentice->fresh()->coach_id)->toBe($second->id);

        $this->actingAs($admin)->put(route('apprentices.coach.update', $apprentice), ['coach_id' => null])->assertRedirect();
        expect($apprentice->fresh()->coach_id)->toBeNull();
    });

    test('cannot make a non-coach the coach', function () {
        $admin = User::factory()->admin()->create();
        $apprentice = makeApprentice();

        $this->actingAs($admin)
            ->put(route('apprentices.coach.update', $apprentice), ['coach_id' => User::factory()->trainer()->create()->id])
            ->assertSessionHasErrors('coach_id');

        expect($apprentice->fresh()->coach_id)->toBeNull();
    });

    test('has no trainer select: trainers follow the plan of the apprentice', function () {
        $it = section(FormationPlan::IT);
        $trainer = makeTrainer($it);
        $apprentice = makeApprentice($it);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('apprentisdashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->missing('trainers')
                ->where('apprentices.0.trainer', $trainer->name));

        expect($trainer->supervises($apprentice))->toBeTrue();
    });
});
