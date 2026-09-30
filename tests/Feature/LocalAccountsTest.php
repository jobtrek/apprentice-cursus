<?php

use App\Enums\UserRole;
use App\Models\Apprenticeship;
use App\Models\User;
use Database\Seeders\ApprenticeshipSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoApprenticeSeeder;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    // Password login and the local accounts only exist in the local environment.
    app()->detectEnvironment(fn () => 'local');

    $this->seed(DatabaseSeeder::class);

    // POST /login is only registered at route-load time when local, so register it now.
    Route::middleware('web')->group(base_path('routes/auth.php'));
    app('router')->getRoutes()->refreshNameLookups();

    // The CSRF middleware only skips tests when the env is "testing", which we just left.
    $this->withoutMiddleware(PreventRequestForgery::class);
});

dataset('local accounts', [
    'admin' => ['admin@example.com', UserRole::Admin, null, 'apprentisdashboard'],
    'coach' => ['coach@example.com', UserRole::Coach, null, 'apprentisdashboard'],
    'trainer' => ['trainer@example.com', UserRole::Trainer, ApprenticeshipSeeder::IT, 'apprentisdashboard'],
    'apprentice IT' => ['apprentice-it@example.com', UserRole::Apprentice, ApprenticeshipSeeder::IT, 'grades.dashboard'],
    'apprentice EC' => ['apprentice-ec@example.com', UserRole::Apprentice, ApprenticeshipSeeder::EC, 'grades.dashboard'],
]);

it('seeds one active account per role with the right section', function (string $email, UserRole $role, ?string $section) {
    $user = User::query()->where('email', $email)->first();

    expect($user)->not->toBeNull();
    expect($user->roles()->count())->toBe(1);
    expect($user->role)->toBe($role);
    expect($user->apprenticeship_id)->toBe($section === null ? null : Apprenticeship::where('name', $section)->value('id'));
    expect($user->is_active)->toBeTrue();
})->with('local accounts');

it('logs in with the local password and lands on the role page', function (string $email, UserRole $role, ?string $section, string $route) {
    $user = User::query()->where('email', $email)->firstOrFail();

    $this->post('/login', ['email' => $email, 'password' => 'password'])
        ->assertRedirect(route($route));

    $this->assertAuthenticatedAs($user);
})->with('local accounts');

it('makes the local coach and admin supervise both local apprentices and the trainer only the IT one', function () {
    $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
    $coach = User::query()->where('email', 'coach@example.com')->firstOrFail();
    $trainer = User::query()->where('email', 'trainer@example.com')->firstOrFail();
    $apprenticeIt = User::query()->where('email', 'apprentice-it@example.com')->firstOrFail();
    $apprenticeEc = User::query()->where('email', 'apprentice-ec@example.com')->firstOrFail();

    expect($apprenticeIt->coach_id)->toBe($coach->id);
    expect($apprenticeEc->coach_id)->toBe($coach->id);
    expect($coach->supervises($apprenticeIt))->toBeTrue();
    expect($coach->supervises($apprenticeEc))->toBeTrue();
    expect($admin->supervises($apprenticeIt))->toBeTrue();
    expect($admin->supervises($apprenticeEc))->toBeTrue();
    expect($trainer->supervises($apprenticeIt))->toBeTrue();
    expect($trainer->supervises($apprenticeEc))->toBeFalse();
});

it('does not let demo apprentices log in with the local password', function () {
    $this->post('/login', ['email' => DemoApprenticeSeeder::email(1), 'password' => 'password'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('is idempotent when seeded twice', function () {
    $this->seed(DatabaseSeeder::class);

    expect(User::query()->whereIn('email', [
        'admin@example.com',
        'coach@example.com',
        'trainer@example.com',
        'apprentice-it@example.com',
        'apprentice-ec@example.com',
    ])->count())->toBe(5);
    expect(User::query()->where('email', 'test@example.com')->exists())->toBeFalse();
});
