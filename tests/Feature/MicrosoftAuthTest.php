<?php

use App\Enums\AzureGroup;
use App\Enums\UserRole;
use App\Models\Apprenticeship;
use App\Models\User;
use App\Services\AzureAccountSync;
use Database\Seeders\ApprenticeshipSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

const IT_GROUP = 'group-it';
const TRAINER_GROUP = 'group-trainer';

beforeEach(function () {
    Cache::flush();
    config()->set('services.azure.groups', [
        AzureGroup::ApprenticesIt->value => IT_GROUP,
        AzureGroup::Trainer->value => TRAINER_GROUP,
    ]);
    Apprenticeship::query()->create(['name' => ApprenticeshipSeeder::IT]);
});

function fakeSso(string $azureId, string $email, string $groupId): void
{
    $socialite = (new SocialiteUser)->map([
        'id' => $azureId,
        'name' => 'Sso User',
        'email' => $email,
    ]);

    Socialite::shouldReceive('driver->user')->andReturn($socialite);

    Http::fake([
        'login.microsoftonline.com/*' => Http::response(['access_token' => 'token']),
        'graph.microsoft.com/*' => Http::response(['value' => [['id' => $groupId, 'displayName' => 'g']]]),
    ]);
}

test('an existing account with the same email is not adopted', function () {
    $existing = User::factory()->create(['email' => 'same@example.test']);
    fakeSso('azure-1', 'same@example.test', IT_GROUP);

    $this->get(route('microsoft.callback'))->assertRedirect(route('login'));

    expect($existing->fresh()->azure_id)->toBeNull();
    $this->assertGuest();
});

test('an unknown azure id provisions a new account', function () {
    fakeSso('azure-2', 'new@example.test', IT_GROUP);

    $this->get(route('microsoft.callback'))->assertRedirect(route('grades.dashboard'));

    $user = User::where('azure_id', 'azure-2')->first();
    expect($user)->not->toBeNull()
        ->and($user->role)->toBe(UserRole::Apprentice);
    $this->assertAuthenticatedAs($user);
});

test('a trainer logs in with the IT apprenticeship and can view an IT apprentice grade', function () {
    fakeSso('azure-trainer', 'trainer@example.test', TRAINER_GROUP);

    $this->get(route('microsoft.callback'))->assertRedirect(route('apprentisdashboard'));

    $trainer = User::where('azure_id', 'azure-trainer')->firstOrFail();
    expect($trainer->role)->toBe(UserRole::Trainer)
        ->and($trainer->apprenticeship->name)->toBe(ApprenticeshipSeeder::IT);

    $apprentice = makeApprentice($trainer->apprenticeship);
    $grade = makeGrade($apprentice);

    $this->get(route('grades.show', $grade))->assertOk();
    $this->get(route('apprentices.grades.show', [$apprentice, $grade]))->assertOk();
});

test('login primes the periodic account re-check cache', function () {
    fakeSso('azure-3', 'cache@example.test', IT_GROUP);

    $this->get(route('microsoft.callback'))->assertRedirect(route('grades.dashboard'));

    $user = User::where('azure_id', 'azure-3')->firstOrFail();
    expect(Cache::has(AzureAccountSync::checkCacheKey($user)))->toBeTrue();
});

test('a section change mid-session keeps the old apprenticeship and does not log out', function () {
    $ec = Apprenticeship::query()->create(['name' => ApprenticeshipSeeder::EC]);
    $user = User::factory()->create(['azure_id' => 'azure-4']);
    $user->forceFill(['apprenticeship_id' => $ec->id])->save();

    Http::fake([
        'login.microsoftonline.com/*' => Http::response(['access_token' => 'token']),
        'graph.microsoft.com/v1.0/users/azure-4' => Http::response(['accountEnabled' => true]),
        'graph.microsoft.com/*' => Http::response(['value' => [['id' => IT_GROUP, 'displayName' => 'g']]]),
    ]);

    $this->actingAs($user)->get(route('home'))->assertOk();

    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->apprenticeship_id)->toBe($ec->id)
        ->and(Cache::has(AzureAccountSync::checkCacheKey($user)))->toBeTrue();
});

test('a Graph outage fails open and backs off', function () {
    $user = User::factory()->create(['azure_id' => 'azure-5']);

    Http::fake([
        'login.microsoftonline.com/*' => Http::response(['access_token' => 'token']),
        'graph.microsoft.com/*' => Http::response([], 500),
    ]);

    $this->actingAs($user)->get(route('home'))->assertOk();

    expect(Cache::has(AzureAccountSync::checkCacheKey($user)))->toBeTrue();
});

test('revoked access deactivates the user and ends the session', function () {
    $user = User::factory()->create(['azure_id' => 'azure-6']);

    Http::fake([
        'login.microsoftonline.com/*' => Http::response(['access_token' => 'token']),
        'graph.microsoft.com/v1.0/users/azure-6' => Http::response(['accountEnabled' => true]),
        'graph.microsoft.com/*' => Http::response(['value' => []]),
    ]);

    $this->actingAs($user)->get(route('home'))->assertRedirect(route('login'));

    $this->assertGuest();
    expect($user->fresh()->is_active)->toBeFalse();
});

test('a deactivated user with a valid group mapping is reactivated on login', function () {
    $user = User::factory()->create(['azure_id' => 'azure-7', 'is_active' => false]);
    fakeSso('azure-7', $user->email, IT_GROUP);

    $this->get(route('microsoft.callback'))->assertRedirect(route('grades.dashboard'));

    expect($user->fresh()->is_active)->toBeTrue();
    $this->assertAuthenticatedAs($user);
});
