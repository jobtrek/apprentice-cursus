<?php

use App\Models\User;
use Database\Seeders\ApprenticeshipSeeder;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

/** @return list<string> */
function listedNames(Assert $page, string $prop = 'apprentices'): array
{
    return collect($page->toArray()['props'][$prop])->pluck('name')->sort()->values()->all();
}

beforeEach(function () {
    $this->it = section(ApprenticeshipSeeder::IT);
    $this->ec = section(ApprenticeshipSeeder::EC);
});

test('a coach lists only the active apprentices it coaches, in both sections', function () {
    $coach = User::factory()->coach()->create();
    $otherCoach = User::factory()->coach()->create(['name' => 'Other coach']);
    makeApprentice($this->it, $coach)->forceFill(['name' => 'A mine IT'])->save();
    makeApprentice($this->ec, $coach)->forceFill(['name' => 'B mine EC'])->save();
    makeApprentice($this->ec, $otherCoach)->forceFill(['name' => 'C other'])->save();
    makeApprentice($this->ec)->forceFill(['name' => 'D none'])->save();
    makeApprentice($this->it, $coach)->forceFill(['name' => 'E gone', 'is_active' => false])->save();

    $this->actingAs($coach)
        ->get(route('apprentisdashboard'))
        ->assertOk()
        ->assertInertia(function (Assert $page) {
            $page->component('ApprentisDashboard');
            expect(listedNames($page))->toBe(['A mine IT', 'B mine EC']);
        });
});

test('each listed apprentice carries its coach and can be opened', function () {
    $coach = User::factory()->coach()->create(['name' => 'My coach']);
    $mine = makeApprentice($this->it, $coach);

    $this->actingAs($coach)
        ->get(route('apprentisdashboard'))
        ->assertInertia(function (Assert $page) use ($mine) {
            $rows = collect($page->toArray()['props']['apprentices'])->keyBy('id');

            expect($rows[$mine->id])->toMatchArray(['track' => 'IT', 'year' => null, 'coach' => 'My coach', 'canView' => true]);
        });
});

test('a trainer lists only its own apprentices of its own section', function () {
    $trainer = User::factory()->trainer()->create();
    $trainer->forceFill(['apprenticeship_id' => $this->it->id])->save();
    makeApprentice($this->it, trainer: $trainer)->forceFill(['name' => 'IT mine'])->save();
    makeApprentice($this->it)->forceFill(['name' => 'IT none'])->save();
    // Assigned but moved to another section: no longer followed.
    makeApprentice($this->ec, trainer: $trainer)->forceFill(['name' => 'EC moved'])->save();

    $this->actingAs($trainer)
        ->get(route('apprentisdashboard'))
        ->assertInertia(fn (Assert $page) => expect(listedNames($page))->toBe(['IT mine']))
        ->assertInertia(fn (Assert $page) => $page->where('apprentices.0.canView', true));
});

test('a trainer without a section lists nobody', function () {
    makeApprentice($this->it);

    $this->actingAs(User::factory()->trainer()->create())
        ->get(route('apprentisdashboard'))
        ->assertInertia(fn (Assert $page) => $page->has('apprentices', 0));
});

test('supervisors are never listed as apprentices', function () {
    $coach = User::factory()->coach()->create();
    // Even with this coach as coach_id, a trainer is not an apprentice.
    User::factory()->trainer()->create()->forceFill(['apprenticeship_id' => $this->it->id, 'coach_id' => $coach->id])->save();

    $this->actingAs($coach)
        ->get(route('apprentisdashboard'))
        ->assertInertia(fn (Assert $page) => $page->has('apprentices', 0));
});

test('an apprentice cannot open the list', function () {
    $this->actingAs(makeApprentice($this->it))->get(route('apprentisdashboard'))->assertForbidden();
});

test('the supervisor home receives the same list, apprentices receive none', function () {
    $coach = User::factory()->coach()->create();
    $apprentice = makeApprentice($this->it, $coach);

    $this->actingAs($coach)
        ->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page->has('apprentices', 1));

    $this->actingAs($apprentice)
        ->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page->has('apprentices', 0));
});

test('the apprentice and grade pages carry the apprentice', function () {
    $coach = User::factory()->coach()->create();
    $apprentice = makeApprentice($this->ec, $coach);
    $apprentice->forceFill(['name' => 'Camille'])->save();
    $grade = makeGrade($apprentice);

    $this->actingAs($coach)
        ->get(route('apprentices.show', $apprentice))
        ->assertInertia(fn (Assert $page) => $page
            ->where('apprentice.name', 'Camille')
            ->where('apprentice.track', 'EC'));

    $this->actingAs($coach)
        ->get(route('apprentices.grades.show', [$apprentice, $grade]))
        ->assertInertia(fn (Assert $page) => $page->where('apprentice.name', 'Camille'));
});

test('the list query count does not depend on the number of apprentices', function () {
    $coach = User::factory()->coach()->create();
    // With a trainer already, so the eager load of `trainer` runs in both counts.
    $trainer = User::factory()->trainer()->create();
    $trainer->forceFill(['apprenticeship_id' => $this->it->id])->save();
    makeApprentice($this->it, $coach, $trainer);

    // Warm-up request: the first one also loads Spatie's permission cache.
    $this->actingAs($coach)->get(route('apprentisdashboard'))->assertOk();

    DB::enableQueryLog();
    $this->actingAs($coach)->get(route('apprentisdashboard'))->assertOk();
    $few = count(DB::getQueryLog());

    foreach (range(1, 5) as $_) {
        $trainer = User::factory()->trainer()->create();
        $trainer->forceFill(['apprenticeship_id' => $this->ec->id])->save();
        makeApprentice($this->ec, $coach, $trainer);
    }

    DB::flushQueryLog();
    $this->actingAs($coach)->get(route('apprentisdashboard'))->assertOk();

    expect(count(DB::getQueryLog()))->toBe($few);
});

test('each apprentice carries its own trainer', function () {
    $coach = User::factory()->coach()->create();
    $trainer = User::factory()->trainer()->create(['name' => 'Bastien Nicoud']);
    $trainer->forceFill(['apprenticeship_id' => $this->it->id])->save();
    $trained = makeApprentice($this->it, $coach, $trainer);
    $untrained = makeApprentice($this->it, $coach);

    $this->actingAs($coach)
        ->get(route('apprentisdashboard'))
        ->assertInertia(function (Assert $page) use ($trained, $untrained, $trainer) {
            $rows = collect($page->toArray()['props']['apprentices'])->keyBy('id');

            expect($rows[$trained->id])->toMatchArray(['trainer' => 'Bastien Nicoud', 'trainerId' => $trainer->id])
                ->and($rows[$untrained->id]['trainer'])->toBeNull();
        });
});
