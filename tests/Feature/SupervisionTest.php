<?php

use App\Models\User;
use Database\Seeders\ApprenticeshipSeeder;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Inertia\Testing\AssertableInertia as Assert;

test('a trainer cannot pick the trainer of an apprentice', function () {
    $this->actingAs(User::factory()->trainer()->create())
        ->put(route('apprentices.trainer.update', makeApprentice()), ['trainer_id' => null])
        ->assertForbidden();
});

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

    test('assigns, changes and removes the trainer of an apprentice', function () {
        $admin = User::factory()->admin()->create();
        $it = section(ApprenticeshipSeeder::IT);
        $first = User::factory()->trainer()->create();
        $second = User::factory()->trainer()->create();
        $first->forceFill(['apprenticeship_context_id' => contextFor($it)->id])->save();
        $second->forceFill(['apprenticeship_context_id' => contextFor($it)->id])->save();
        $apprentice = makeApprentice($it);

        $this->actingAs($admin)->put(route('apprentices.trainer.update', $apprentice), ['trainer_id' => $first->id])->assertRedirect();
        expect($apprentice->fresh()->trainer_id)->toBe($first->id)
            ->and($first->supervises($apprentice->fresh()))->toBeTrue();

        $this->actingAs($admin)->put(route('apprentices.trainer.update', $apprentice), ['trainer_id' => $second->id])->assertRedirect();
        expect($apprentice->fresh()->trainer_id)->toBe($second->id);

        $this->actingAs($admin)->put(route('apprentices.trainer.update', $apprentice), ['trainer_id' => null])->assertRedirect();
        expect($apprentice->fresh()->trainer_id)->toBeNull();
    });

    test('cannot give an apprentice a trainer of another section', function () {
        $admin = User::factory()->admin()->create();
        $itTrainer = User::factory()->trainer()->create();
        $itTrainer->forceFill(['apprenticeship_context_id' => contextFor(section(ApprenticeshipSeeder::IT))->id])->save();
        $ecApprentice = makeApprentice(section(ApprenticeshipSeeder::EC));

        $this->actingAs($admin)
            ->put(route('apprentices.trainer.update', $ecApprentice), ['trainer_id' => $itTrainer->id])
            ->assertSessionHasErrors('trainer_id');

        expect($ecApprentice->fresh()->trainer_id)->toBeNull();
    });

    test('offers the trainers with their section', function () {
        $admin = User::factory()->admin()->create();
        $trainer = User::factory()->trainer()->create();
        $trainer->forceFill(['apprenticeship_context_id' => contextFor(section(ApprenticeshipSeeder::IT))->id])->save();

        $this->actingAs($admin)
            ->get(route('apprentisdashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('trainers', [['id' => $trainer->id, 'name' => $trainer->name, 'track' => 'IT']]));
    });
});
