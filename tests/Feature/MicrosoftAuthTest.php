<?php

use App\Enums\AzureGroup;
use App\Enums\UserRole;
use App\Models\Apprenticeship;
use App\Models\User;
use App\Services\AzureAccountSync;
use Database\Seeders\ApprenticeshipSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

const IT_GROUP = 'group-it';
const TRAINER_GROUP = 'group-trainer';
const COACH_GROUP = 'group-coach';

beforeEach(function () {
    Cache::flush();
    config()->set('services.azure.groups', [
        AzureGroup::ApprenticesIt->value => IT_GROUP,
        AzureGroup::Trainer->value => TRAINER_GROUP,
        AzureGroup::Coach->value => COACH_GROUP,
    ]);
    Apprenticeship::query()->create(['name' => ApprenticeshipSeeder::IT]);
});

const NO_ACCESS_MESSAGE = 'Your Microsoft account has no access to this application. Please contact an administrator.';
const SECTION_CHANGE_WARNING = 'Microsoft SSO: section change pending confirmation, apprenticeship kept.';

function fakeSocialiteUser(string $azureId, string $email): void
{
    $socialite = (new SocialiteUser)->map([
        'id' => $azureId,
        'name' => 'Sso User',
        'email' => $email,
    ]);

    Socialite::shouldReceive('driver->user')->andReturn($socialite);
}

/** @param  string|list<string>  $groupIds */
function fakeSso(string $azureId, string $email, string|array $groupIds): void
{
    fakeSocialiteUser($azureId, $email);

    Http::fake([
        'login.microsoftonline.com/*' => Http::response(['access_token' => 'token']),
        'graph.microsoft.com/*' => Http::response(['value' => array_map(
            fn (string $id) => ['id' => $id, 'displayName' => 'g'],
            (array) $groupIds,
        )]),
    ]);
}

/** Number of requests sent to Microsoft (the Inertia SSR call is also recorded by Http::fake). */
function microsoftRequestCount(): int
{
    return Http::recorded(fn ($request) => str_contains($request->url(), 'microsoft'))->count();
}

/**
 * Fake Graph for an already logged-in user (periodic re-check middleware).
 *
 * @param  list<string>  $groupIds
 * @param  array<string, mixed>  $account
 */
function fakeGraphRecheck(string $azureId, array $groupIds, array $account = ['accountEnabled' => true], int $accountStatus = 200): void
{
    Http::fake([
        'login.microsoftonline.com/*' => Http::response(['access_token' => 'token']),
        "graph.microsoft.com/v1.0/users/{$azureId}?*" => Http::response($account, $accountStatus),
        'graph.microsoft.com/*' => Http::response(['value' => array_map(
            fn (string $id) => ['id' => $id, 'displayName' => 'g'],
            $groupIds,
        )]),
    ]);
}

const NOT_SYNCED_MESSAGE = 'Your account has not been synced yet. Please try again tomorrow or ask an administrator to run the account sync.';

test('an existing account with the same email is not adopted and the login is refused as not synced', function () {
    $existing = User::factory()->create(['email' => 'same@example.test']);
    fakeSso('azure-1', 'same@example.test', IT_GROUP);

    $this->get(route('microsoft.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('error', NOT_SYNCED_MESSAGE);

    expect($existing->fresh()->azure_id)->toBeNull();
    $this->assertGuest();
});

test('an unknown azure id is refused as not synced and no account is created', function () {
    fakeSso('azure-2', 'new@example.test', IT_GROUP);

    $this->get(route('microsoft.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('error', NOT_SYNCED_MESSAGE);

    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['azure_id' => 'azure-2']);
    $this->assertDatabaseMissing('users', ['email' => 'new@example.test']);
});

test('a synced coach logs in with the coach role and no section', function () {
    $it = Apprenticeship::query()->where('name', ApprenticeshipSeeder::IT)->firstOrFail();
    $user = User::factory()->create(['azure_id' => 'azure-coach']);
    $user->forceFill(['apprenticeship_id' => $it->id])->save();
    fakeSso('azure-coach', $user->email, COACH_GROUP);

    $this->get(route('microsoft.callback'))->assertRedirect(route('apprentisdashboard'));

    $this->assertAuthenticatedAs($user);
    $user = $user->fresh();
    expect($user->role)->toBe(UserRole::Coach)
        ->and($user->apprenticeship_id)->toBeNull();
});

test('a trainer logs in with the IT apprenticeship and can view an IT apprentice grade', function () {
    User::factory()->create(['azure_id' => 'azure-trainer', 'email' => 'trainer@example.test']);
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
    User::factory()->create(['azure_id' => 'azure-3', 'email' => 'cache@example.test']);
    fakeSso('azure-3', 'cache@example.test', IT_GROUP);

    $this->get(route('microsoft.callback'))->assertRedirect(route('grades.dashboard'));

    $user = User::where('azure_id', 'azure-3')->firstOrFail();
    expect(Cache::has(AzureAccountSync::checkCacheKey($user)))->toBeTrue();
});

test('a section change mid-session keeps the old apprenticeship and does not log out', function () {
    $ec = Apprenticeship::query()->create(['name' => ApprenticeshipSeeder::EC]);
    $user = User::factory()->create(['azure_id' => 'azure-4']);
    $user->forceFill(['apprenticeship_id' => $ec->id])->save();

    Log::spy();
    fakeGraphRecheck('azure-4', [IT_GROUP]);

    $this->actingAs($user)->get(route('home'))->assertOk();

    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->apprenticeship_id)->toBe($ec->id)
        ->and(Cache::has(AzureAccountSync::checkCacheKey($user)))->toBeTrue();
    Log::shouldHaveReceived('warning')->with(SECTION_CHANGE_WARNING, Mockery::type('array'))->once();
});

test('a section change at login keeps the old apprenticeship, logs a warning and logs in', function () {
    $ec = Apprenticeship::query()->create(['name' => ApprenticeshipSeeder::EC]);
    $user = User::factory()->create(['azure_id' => 'azure-8']);
    $user->forceFill(['apprenticeship_id' => $ec->id])->save();
    Log::spy();
    fakeSso('azure-8', $user->email, IT_GROUP);

    $this->get(route('microsoft.callback'))->assertRedirect(route('grades.dashboard'));

    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->apprenticeship_id)->toBe($ec->id);
    Log::shouldHaveReceived('warning')->with(SECTION_CHANGE_WARNING, Mockery::type('array'))->once();
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
        'graph.microsoft.com/v1.0/users/azure-6?*' => Http::response(['accountEnabled' => true]),
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

test('a Graph failure at login refuses the login and creates no account', function () {
    fakeSocialiteUser('azure-9', 'graph-down@example.test');
    Http::fake([
        'login.microsoftonline.com/*' => Http::response(['access_token' => 'token']),
        'graph.microsoft.com/*' => Http::response([], 500),
    ]);

    $this->get(route('microsoft.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('error', 'Could not verify your Microsoft groups. Please try again later.');

    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['azure_id' => 'azure-9']);
    $this->assertDatabaseMissing('users', ['email' => 'graph-down@example.test']);
});

test('a Graph failure in the middleware backs off for a minute', function () {
    $user = User::factory()->create(['azure_id' => 'azure-10']);
    Http::fake([
        'login.microsoftonline.com/*' => Http::response(['access_token' => 'token']),
        'graph.microsoft.com/*' => Http::response([], 500),
    ]);

    $this->actingAs($user)->get(route('home'))->assertOk();

    expect(Cache::has(AzureAccountSync::checkCacheKey($user)))->toBeTrue();
    expect(microsoftRequestCount())->toBe(2);

    $this->get(route('home'))->assertOk();

    expect(microsoftRequestCount())->toBe(2);

    $this->travel(AzureAccountSync::BACKOFF_SECONDS + 1)->seconds();

    expect(Cache::has(AzureAccountSync::checkCacheKey($user)))->toBeFalse();
    $this->get(route('home'))->assertOk();

    expect(microsoftRequestCount())->toBe(3);
    $this->assertAuthenticatedAs($user);
});

test('a login for an account in no mapped group is refused and deactivates the existing user', function () {
    $user = User::factory()->create(['azure_id' => 'azure-11']);
    fakeSso('azure-11', $user->email, 'group-unmapped');

    $this->get(route('microsoft.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('error', NO_ACCESS_MESSAGE);

    $this->assertGuest();
    expect($user->fresh()->is_active)->toBeFalse();
});

test('a login for an account in several mapped groups is refused and deactivates the existing user', function () {
    $user = User::factory()->create(['azure_id' => 'azure-12']);
    fakeSso('azure-12', $user->email, [IT_GROUP, TRAINER_GROUP]);

    $this->get(route('microsoft.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('error', NO_ACCESS_MESSAGE);

    $this->assertGuest();
    expect($user->fresh()->is_active)->toBeFalse();
});

test('a refused new user never gets a users row', function () {
    fakeSso('azure-13', 'refused@example.test', 'group-unmapped');

    $this->get(route('microsoft.callback'))->assertRedirect(route('login'));

    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['azure_id' => 'azure-13']);
    $this->assertDatabaseMissing('users', ['email' => 'refused@example.test']);
});

test('a refused new user in several mapped groups never gets a users row', function () {
    fakeSso('azure-14', 'both@example.test', [IT_GROUP, TRAINER_GROUP]);

    $this->get(route('microsoft.callback'))->assertRedirect(route('login'));

    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['azure_id' => 'azure-14']);
});

test('a user in several mapped groups is deactivated and logged out by the middleware', function () {
    $user = User::factory()->create(['azure_id' => 'azure-15']);
    fakeGraphRecheck('azure-15', [IT_GROUP, TRAINER_GROUP]);

    $this->actingAs($user)->get(route('home'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('error', 'Your Microsoft account no longer has access to this application. Please contact an administrator.');

    $this->assertGuest();
    expect($user->fresh()->is_active)->toBeFalse();
});

test('a role change mid-session updates the Spatie role and keeps the session', function () {
    $it = Apprenticeship::query()->where('name', ApprenticeshipSeeder::IT)->firstOrFail();
    $user = User::factory()->create(['azure_id' => 'azure-16']);
    $user->forceFill(['apprenticeship_id' => $it->id])->save();
    expect($user->role)->toBe(UserRole::Apprentice);
    fakeGraphRecheck('azure-16', [TRAINER_GROUP]);

    $this->actingAs($user)->get(route('home'))->assertOk();

    $this->assertAuthenticatedAs($user);
    $user = $user->fresh();
    expect($user->role)->toBe(UserRole::Trainer)
        ->and($user->roles->pluck('name')->all())->toBe(['trainer'])
        ->and($user->apprenticeship_id)->toBe($it->id);
});

test('a disabled Entra account is deactivated and logged out by the middleware', function () {
    $user = User::factory()->create(['azure_id' => 'azure-17']);
    fakeGraphRecheck('azure-17', [IT_GROUP], ['accountEnabled' => false]);

    $this->actingAs($user)->get(route('home'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('error', 'Your Microsoft account is no longer active. Please contact an administrator.');

    $this->assertGuest();
    expect($user->fresh()->is_active)->toBeFalse();
});

test('an account missing from Entra (Graph 404) is deactivated and logged out by the middleware', function () {
    $user = User::factory()->create(['azure_id' => 'azure-18']);
    fakeGraphRecheck('azure-18', [IT_GROUP], ['error' => ['code' => 'Request_ResourceNotFound']], 404);

    $this->actingAs($user)->get(route('home'))->assertRedirect(route('login'));

    $this->assertGuest();
    expect($user->fresh()->is_active)->toBeFalse();
});

test('an apprentice moved to the trainer group mid-session gets the trainer role and the IT apprenticeship', function () {
    $ec = Apprenticeship::query()->create(['name' => ApprenticeshipSeeder::EC]);
    $it = Apprenticeship::query()->where('name', ApprenticeshipSeeder::IT)->firstOrFail();
    $user = User::factory()->create(['azure_id' => 'azure-19']);
    $user->forceFill(['apprenticeship_id' => $ec->id])->save();

    Log::spy();
    fakeGraphRecheck('azure-19', [TRAINER_GROUP]);

    $this->actingAs($user)->get(route('home'))->assertOk();

    $this->assertAuthenticatedAs($user);
    $user = $user->fresh();
    expect($user->role)->toBe(UserRole::Trainer)
        ->and($user->apprenticeship_id)->toBe($it->id);
    Log::shouldNotHaveReceived('warning', [SECTION_CHANGE_WARNING, Mockery::type('array')]);
});

test('a missing apprenticeship row on re-check ends the session without deactivating or changing the user', function () {
    config()->set('services.azure.groups.'.AzureGroup::ApprenticesEc->value, 'group-ec');
    $it = Apprenticeship::query()->where('name', ApprenticeshipSeeder::IT)->firstOrFail();
    $user = User::factory()->trainer()->create(['azure_id' => 'azure-20']);
    $user->forceFill(['apprenticeship_id' => $it->id])->save();
    fakeGraphRecheck('azure-20', ['group-ec']);

    $this->actingAs($user)->get(route('home'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('error', 'Could not verify your apprenticeship. Please contact an administrator.');

    $this->assertGuest();
    $user = $user->fresh();
    expect($user->is_active)->toBeTrue()
        ->and($user->role)->toBe(UserRole::Trainer)
        ->and($user->apprenticeship_id)->toBe($it->id)
        ->and(Cache::has(AzureAccountSync::checkCacheKey($user)))->toBeFalse();
});
