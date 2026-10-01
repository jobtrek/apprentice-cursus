<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoApprenticeSeeder;

test('demo coach backfill skips pre-existing accounts at demo ids', function () {
    // The demo path only runs in the local environment.
    app()->detectEnvironment(fn () => 'local');
    expect(app()->environment('local'))->toBeTrue();

    // Unrelated account occupying a demo id (no factory: roles are not seeded yet).
    User::query()->forceCreate([
        'id' => 1,
        'name' => 'Someone Else',
        'email' => 'someone@example.com',
        'password' => 'irrelevant',
        'is_active' => true,
    ]);

    $this->seed(DatabaseSeeder::class);

    $coachId = User::query()->where('email', 'coach@example.com')->value('id');
    expect($coachId)->not->toBeNull();

    $existing = User::query()->findOrFail(1);
    expect($existing->email)->toBe('someone@example.com');
    expect($existing->coach_id)->toBeNull();

    foreach (range(2, DemoApprenticeSeeder::COUNT) as $id) {
        $demo = User::query()->where('email', DemoApprenticeSeeder::email($id))->first();

        expect($demo)->not->toBeNull();
        expect($demo->coach_id)->toBe($coachId);
    }
});
