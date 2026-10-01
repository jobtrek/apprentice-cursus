<?php

use App\Models\User;
use Database\Seeders\ApprenticeshipSeeder;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Inertia\Testing\AssertableInertia as Assert;

describe('coach', function () {
    test('sees only their own apprentices and the ones without a coach to add', function () {
        $coach = User::factory()->coach()->create();
        $own = makeApprentice(coach: $coach);
        $free = makeApprentice();
        makeApprentice(coach: User::factory()->coach()->create());

        $this->actingAs($coach)
            ->get(route('apprentisdashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('apprentices', 1)
                ->where('apprentices.0.id', $own->id)
                ->has('assignable', 1)
                ->where('assignable.0.id', $free->id)
                ->where('can.assignSelfAs', 'coach')
                ->where('can.manageSupervision', false)
                ->where('coaches', []));
    });

    test('can add an apprentice without a coach', function () {
        $coach = User::factory()->coach()->create();
        $free = makeApprentice();

        $this->actingAs($coach)
            ->post(route('apprentices.coach.assign-self', $free))
            ->assertRedirect();

        expect($free->fresh()->coach_id)->toBe($coach->id);
    });

    test('cannot take an apprentice that already has a coach', function () {
        $taken = makeApprentice(coach: $first = User::factory()->coach()->create());

        $this->actingAs(User::factory()->coach()->create())
            ->post(route('apprentices.coach.assign-self', $taken))
            ->assertForbidden();

        expect($taken->fresh()->coach_id)->toBe($first->id);
    });

    test('cannot take an inactive apprentice', function () {
        $inactive = makeApprentice();
        $inactive->forceFill(['is_active' => false])->save();

        $this->actingAs(User::factory()->coach()->create())
            ->post(route('apprentices.coach.assign-self', $inactive))
            ->assertForbidden();
    });

    test('cannot add itself as trainer', function () {
        $this->actingAs(User::factory()->coach()->create())
            ->post(route('apprentices.trainer.assign-self', makeApprentice()))
            ->assertForbidden();
    });

    test('cannot pick the trainer of an apprentice', function () {
        $this->actingAs(User::factory()->coach()->create())
            ->put(route('apprentices.trainer.update', makeApprentice()), ['trainer_id' => null])
            ->assertForbidden();
    });

    test('cannot pick another coach for an apprentice', function () {
        $apprentice = makeApprentice();

        $this->actingAs(User::factory()->coach()->create())
            ->put(route('apprentices.coach.update', $apprentice), ['coach_id' => null])
            ->assertForbidden();
    });
});

describe('trainer', function () {
    beforeEach(function () {
        $this->it = section(ApprenticeshipSeeder::IT);
        $this->ec = section(ApprenticeshipSeeder::EC);
        $this->trainer = User::factory()->trainer()->create();
        $this->trainer->forceFill(['apprenticeship_id' => $this->it->id])->save();
    });

    test('sees only its own apprentices and the free ones of its section to add', function () {
        $own = makeApprentice($this->it, trainer: $this->trainer);
        $free = makeApprentice($this->it);
        makeApprentice($this->ec);
        makeApprentice($this->it, trainer: User::factory()->trainer()->create());

        $this->actingAs($this->trainer)
            ->get(route('apprentisdashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('apprentices', 1)
                ->where('apprentices.0.id', $own->id)
                ->where('apprentices.0.track', 'IT')
                ->where('apprentices.0.trainer', ['id' => $this->trainer->id, 'name' => $this->trainer->name])
                ->has('assignable', 1)
                ->where('assignable.0.id', $free->id)
                ->where('can.assignSelfAs', 'trainer'));
    });

    test('can add an apprentice of its section without a trainer', function () {
        $free = makeApprentice($this->it);

        $this->actingAs($this->trainer)
            ->post(route('apprentices.trainer.assign-self', $free))
            ->assertRedirect();

        expect($free->fresh()->trainer_id)->toBe($this->trainer->id);
    });

    test('cannot add an apprentice of another section', function () {
        $ecApprentice = makeApprentice($this->ec);

        $this->actingAs($this->trainer)
            ->post(route('apprentices.trainer.assign-self', $ecApprentice))
            ->assertForbidden();

        expect($ecApprentice->fresh()->trainer_id)->toBeNull();
    });

    test('cannot take an apprentice that already has a trainer', function () {
        $taken = makeApprentice($this->it, trainer: $other = User::factory()->trainer()->create());

        $this->actingAs($this->trainer)
            ->post(route('apprentices.trainer.assign-self', $taken))
            ->assertForbidden();

        expect($taken->fresh()->trainer_id)->toBe($other->id);
    });

    test('of EC sees and adds only EC apprentices', function () {
        $ecTrainer = User::factory()->trainer()->create();
        $ecTrainer->forceFill(['apprenticeship_id' => $this->ec->id])->save();
        makeApprentice($this->it);
        $freeEc = makeApprentice($this->ec);

        $this->actingAs($ecTrainer)
            ->get(route('apprentisdashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('apprentices', 0)
                ->has('assignable', 1)
                ->where('assignable.0.id', $freeEc->id)
                ->where('assignable.0.track', 'EC'));
    });

    test('cannot use the coach actions', function () {
        $this->actingAs($this->trainer)
            ->post(route('apprentices.coach.assign-self', makeApprentice($this->it)))
            ->assertForbidden();
    });
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
                ->where('can.assignSelfAs', null)
                ->where('assignable', []));
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

    test('assigns, changes and removes the trainer of an apprentice', function () {
        $admin = User::factory()->admin()->create();
        $it = section(ApprenticeshipSeeder::IT);
        $first = User::factory()->trainer()->create();
        $second = User::factory()->trainer()->create();
        $first->forceFill(['apprenticeship_id' => $it->id])->save();
        $second->forceFill(['apprenticeship_id' => $it->id])->save();
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
        $itTrainer->forceFill(['apprenticeship_id' => section(ApprenticeshipSeeder::IT)->id])->save();
        $ecApprentice = makeApprentice(section(ApprenticeshipSeeder::EC));

        $this->actingAs($admin)
            ->put(route('apprentices.trainer.update', $ecApprentice), ['trainer_id' => $itTrainer->id])
            ->assertSessionHasErrors('trainer_id');

        expect($ecApprentice->fresh()->trainer_id)->toBeNull();
    });

    test('offers the trainers with their section', function () {
        $admin = User::factory()->admin()->create();
        $trainer = User::factory()->trainer()->create();
        $trainer->forceFill(['apprenticeship_id' => section(ApprenticeshipSeeder::IT)->id])->save();

        $this->actingAs($admin)
            ->get(route('apprentisdashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('trainers', [['id' => $trainer->id, 'name' => $trainer->name, 'track' => 'IT']]));
    });

    test('cannot make a non-coach the coach', function () {
        $admin = User::factory()->admin()->create();
        $apprentice = makeApprentice();

        $this->actingAs($admin)
            ->put(route('apprentices.coach.update', $apprentice), ['coach_id' => User::factory()->trainer()->create()->id])
            ->assertSessionHasErrors('coach_id');

        expect($apprentice->fresh()->coach_id)->toBeNull();
    });
});
