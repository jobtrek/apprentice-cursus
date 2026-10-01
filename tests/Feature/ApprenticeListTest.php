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

test('a coach lists every active apprentice of both sections, assigned or not', function () {
    $coach = User::factory()->coach()->create();
    $otherCoach = User::factory()->coach()->create(['name' => 'Other coach']);
    makeApprentice($this->it, $coach)->forceFill(['name' => 'A mine'])->save();
    makeApprentice($this->ec, $otherCoach)->forceFill(['name' => 'B other'])->save();
    makeApprentice($this->ec)->forceFill(['name' => 'C none'])->save();
    makeApprentice($this->it)->forceFill(['name' => 'D gone', 'is_active' => false])->save();

    $this->actingAs($coach)
        ->get(route('apprentisdashboard'))
        ->assertOk()
        ->assertInertia(function (Assert $page) {
            $page->component('ApprentisDashboard');
            expect(listedNames($page))->toBe(['A mine', 'B other', 'C none']);
        });
});

test('a coach can open only its coachees from the list', function () {
    $coach = User::factory()->coach()->create(['name' => 'My coach']);
    $mine = makeApprentice($this->it, $coach);
    $notMine = makeApprentice($this->ec);

    $this->actingAs($coach)
        ->get(route('apprentisdashboard'))
        ->assertInertia(function (Assert $page) use ($mine, $notMine) {
            $rows = collect($page->toArray()['props']['apprentices'])->keyBy('id');

            expect($rows[$mine->id])->toMatchArray(['track' => 'IT', 'year' => null, 'coach' => 'My coach', 'canView' => true]);
            expect($rows[$notMine->id])->toMatchArray(['track' => 'EC', 'coach' => null, 'canView' => false]);
        });
});

test('a trainer lists only the apprentices of its own section', function () {
    $trainer = User::factory()->trainer()->create();
    $trainer->forceFill(['apprenticeship_id' => $this->it->id])->save();
    makeApprentice($this->it)->forceFill(['name' => 'IT one'])->save();
    makeApprentice($this->ec)->forceFill(['name' => 'EC one'])->save();

    $this->actingAs($trainer)
        ->get(route('apprentisdashboard'))
        ->assertInertia(fn (Assert $page) => expect(listedNames($page))->toBe(['IT one']))
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
    User::factory()->trainer()->create()->forceFill(['apprenticeship_id' => $this->it->id])->save();

    $this->actingAs($coach)
        ->get(route('apprentisdashboard'))
        ->assertInertia(fn (Assert $page) => $page->has('apprentices', 0));
});

test('an apprentice cannot open the list', function () {
    $this->actingAs(makeApprentice($this->it))->get(route('apprentisdashboard'))->assertForbidden();
});

test('the supervisor home receives the same list, apprentices receive none', function () {
    $coach = User::factory()->coach()->create();
    $apprentice = makeApprentice($this->it);

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
    makeApprentice($this->it, $coach);

    // Warm-up request: the first one also loads Spatie's permission cache.
    $this->actingAs($coach)->get(route('apprentisdashboard'))->assertOk();

    DB::enableQueryLog();
    $this->actingAs($coach)->get(route('apprentisdashboard'))->assertOk();
    $few = count(DB::getQueryLog());

    foreach (range(1, 5) as $_) {
        makeApprentice($this->ec, User::factory()->coach()->create());
    }

    DB::flushQueryLog();
    $this->actingAs($coach)->get(route('apprentisdashboard'))->assertOk();

    expect(count(DB::getQueryLog()))->toBe($few);
});
