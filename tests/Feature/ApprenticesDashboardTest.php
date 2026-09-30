<?php

use App\Models\User;
use Database\Seeders\ApprenticeshipSeeder;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

function named(string $name, $section = null, ?User $coach = null): User
{
    $apprentice = makeApprentice($section, $coach);
    $apprentice->forceFill(['name' => $name])->save();

    return $apprentice;
}

test('a trainer sees own apprenticeship apprentices, inactive included, sorted by name', function () {
    $it = section(ApprenticeshipSeeder::IT);
    $ec = section(ApprenticeshipSeeder::EC);
    $trainer = User::factory()->trainer()->create();
    $trainer->forceFill(['apprenticeship_id' => $it->id])->save();

    named('Zoe', $it);
    $inactive = named('Adam', $it);
    $inactive->forceFill(['is_active' => false])->save();
    named('Other', $ec);
    $coach = User::factory()->coach()->create();
    $coach->forceFill(['apprenticeship_id' => $it->id, 'name' => 'Aaa Coach'])->save();

    $this->actingAs($trainer)->get(route('apprentisdashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('ApprentisDashboard')
            ->has('apprentices', 2)
            ->where('apprentices.0', [
                'id' => $inactive->id,
                'name' => 'Adam',
                'apprenticeship' => 'IT',
                'coach' => null,
                'isActive' => false,
            ])
            ->where('apprentices.1.name', 'Zoe'));
});

test('a coach sees only own coachees across apprenticeships', function () {
    $it = section(ApprenticeshipSeeder::IT);
    $ec = section(ApprenticeshipSeeder::EC);
    $coach = User::factory()->coach()->create();
    $coach->forceFill(['name' => 'Coach Carl'])->save();

    named('Mine IT', $it, $coach);
    named('Mine EC', $ec, $coach);
    named('Not mine', $it, User::factory()->coach()->create());

    $this->actingAs($coach)->get(route('apprentisdashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('apprentices', 2)
            ->where('apprentices.0.name', 'Mine EC')
            ->where('apprentices.0.apprenticeship', 'EC')
            ->where('apprentices.0.coach', 'Coach Carl')
            ->where('apprentices.1.name', 'Mine IT'));
});

test('a local admin sees all apprentices', function () {
    app()->detectEnvironment(fn () => 'local');
    named('One', section('Unknown'));
    named('Two');

    $this->actingAs(User::factory()->admin()->create())->get(route('apprentisdashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('apprentices', 2)
            ->where('apprentices.0.apprenticeship', null));
});

test('the list does not run a query per apprentice', function () {
    $it = section(ApprenticeshipSeeder::IT);
    $coach = User::factory()->coach()->create();
    foreach (range(1, 6) as $i) {
        named("A{$i}", $it, $coach);
    }

    // Warm-up request: the first one also loads permission caches.
    $this->actingAs($coach)->get(route('apprentisdashboard'))->assertOk();
    DB::enableQueryLog();
    DB::flushQueryLog();
    $this->get(route('apprentisdashboard'))->assertOk();
    $count = count(DB::getQueryLog());
    DB::flushQueryLog();

    foreach (range(7, 12) as $i) {
        named("A{$i}", $it, $coach);
    }
    DB::flushQueryLog();
    $this->get(route('apprentisdashboard'))->assertOk();

    expect(count(DB::getQueryLog()))->toBe($count);
});

test('show and grade pages expose the apprentice prop', function () {
    $it = section(ApprenticeshipSeeder::IT);
    $coach = User::factory()->coach()->create();
    $apprentice = named('Ann', $it, $coach);
    $grade = makeGrade($apprentice);
    $expected = [
        'id' => $apprentice->id,
        'name' => 'Ann',
        'apprenticeship' => 'IT',
        'coach' => $coach->name,
        'isActive' => true,
    ];

    $this->actingAs($coach)->get(route('apprentices.show', $apprentice))
        ->assertInertia(fn (Assert $page) => $page->where('apprentice', $expected)->missing('apprenticeId'));
    $this->actingAs($coach)->get(route('apprentices.grades.show', [$apprentice, $grade]))
        ->assertInertia(fn (Assert $page) => $page->where('apprentice', $expected)->missing('apprenticeId'));
});

test('home exposes apprentices to supervisors and none to apprentices', function () {
    $coach = User::factory()->coach()->create();
    named('Ann', null, $coach);

    $this->actingAs($coach)->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page->component('Home')->has('apprentices', 1)->where('apprentices.0.name', 'Ann'));

    $this->actingAs(User::factory()->create())->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page->where('apprentices', []));
});

test('supervisedBy agrees with supervises()', function () {
    $it = section('IT');
    $ec = section('EC');
    $coach = User::factory()->coach()->create();
    $trainer = User::factory()->trainer()->create();
    $trainer->forceFill(['apprenticeship_id' => $it->id])->save();
    $lonelyTrainer = User::factory()->trainer()->create();

    $candidates = [
        named('a', $it, $coach), named('b', $ec, $coach), named('c', $it), named('d', $ec),
        named('e'), $coach, $trainer, User::factory()->create(),
    ];

    foreach ([$coach, $trainer, $lonelyTrainer, $candidates[7]] as $viewer) {
        $expected = collect([...$candidates, $viewer])->filter(fn (User $u) => $viewer->supervises($u))
            ->pluck('id')->unique()->sort()->values()->all();
        $actual = User::query()->supervisedBy($viewer)->pluck('id')->sort()->values()->all();

        expect($actual)->toBe($expected);
    }
});
