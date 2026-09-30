<?php

use App\Enums\UserRole;
use App\Models\Apprenticeship;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

const GROUP_IT = 'group-it';
const GROUP_EC = 'group-ec';
const GROUP_TRAINER = 'group-trainer';
const GROUP_COACH = 'group-coach';

beforeEach(function () {
    config()->set('services.azure.group_roles', [
        GROUP_IT => 'apprentices_IT',
        GROUP_EC => 'apprentices_EC',
        GROUP_TRAINER => 'trainer',
        GROUP_COACH => 'coach',
    ]);
    Cache::flush();
});

/**
 * @param  list<string>|null  $groupIds  null simulates a Graph outage
 */
function fakeGraph(?array $groupIds, bool $enabled = true): void
{
    Http::fake([
        'login.microsoftonline.com/*' => Http::response(['access_token' => 'token']),
        'graph.microsoft.com/v1.0/users/*/transitiveMemberOf*' => $groupIds === null
            ? Http::response(status: 503)
            : Http::response(['value' => array_map(fn (string $id) => ['id' => $id], $groupIds)]),
        'graph.microsoft.com/v1.0/users/*' => Http::response(['accountEnabled' => $enabled]),
    ]);
}

function fakeSocialiteUser(string $azureId = 'azure-1', string $email = 'someone@jobtrek.ch'): void
{
    $user = (new SocialiteUser)->map([
        'id' => $azureId,
        'name' => 'Someone',
        'email' => $email,
    ]);

    Socialite::shouldReceive('driver->user')->andReturn($user);
}

function apprenticeshipId(string $code): int
{
    return (int) Apprenticeship::where('code', $code)->value('id');
}

test('login maps each Entra group to its role and section', function (string $group, UserRole $role, ?string $code) {
    fakeGraph([$group, 'some-unrelated-group']);
    fakeSocialiteUser();

    $this->get(route('microsoft.callback'))->assertRedirect(route('home'));

    $user = User::where('azure_id', 'azure-1')->sole();
    expect($user->role)->toBe($role)
        ->and($user->apprenticeship_id)->toBe($code ? apprenticeshipId($code) : null)
        ->and($user->is_active)->toBeTrue();
    $this->assertAuthenticatedAs($user);
})->with([
    'IT apprentice' => [GROUP_IT, UserRole::Apprentice, Apprenticeship::IT],
    'EC apprentice' => [GROUP_EC, UserRole::Apprentice, Apprenticeship::EC],
    'trainer (IT until an EC group exists)' => [GROUP_TRAINER, UserRole::Trainer, Apprenticeship::IT],
    'coach' => [GROUP_COACH, UserRole::Coach, null],
]);

test('login updates the role of an existing user', function () {
    $user = User::factory()->apprentice()->create(['azure_id' => 'azure-1']);
    fakeGraph([GROUP_COACH]);
    fakeSocialiteUser();

    $this->get(route('microsoft.callback'))->assertRedirect(route('home'));

    expect($user->fresh()->role)->toBe(UserRole::Coach)
        ->and($user->fresh()->apprenticeship_id)->toBeNull();
});

test('login is refused without a role group', function () {
    fakeGraph(['some-unrelated-group']);
    fakeSocialiteUser();

    $this->get(route('microsoft.callback'))->assertRedirect(route('login'));

    $this->assertGuest();
    expect(User::count())->toBe(0);
});

test('login is refused with more than one role group', function () {
    fakeGraph([GROUP_IT, GROUP_TRAINER]);
    fakeSocialiteUser();

    $this->get(route('microsoft.callback'))->assertRedirect(route('login'));

    $this->assertGuest();
});

test('an existing user who lost their role is deactivated on login', function () {
    $user = User::factory()->apprentice()->create(['azure_id' => 'azure-1']);
    fakeGraph([]);
    fakeSocialiteUser();

    $this->get(route('microsoft.callback'))->assertRedirect(route('login'));

    $this->assertGuest();
    expect($user->fresh()->is_active)->toBeFalse();
});

test('a deactivated user in a role group again is reactivated', function () {
    $user = User::factory()->apprentice()->inactive()->create(['azure_id' => 'azure-1']);
    fakeGraph([GROUP_IT]);
    fakeSocialiteUser();

    $this->get(route('microsoft.callback'))->assertRedirect(route('home'));

    expect($user->fresh()->is_active)->toBeTrue();
});

test('Graph outage keeps an existing user logged in with their stored role', function () {
    $user = User::factory()->trainer()->create(['azure_id' => 'azure-1']);
    fakeGraph(null);
    fakeSocialiteUser();

    $this->get(route('microsoft.callback'))->assertRedirect(route('home'));

    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->role)->toBe(UserRole::Trainer);
});

test('Graph outage refuses a first login', function () {
    fakeGraph(null);
    fakeSocialiteUser();

    $this->get(route('microsoft.callback'))->assertRedirect(route('login'));

    $this->assertGuest();
});

test('users are not linked by email', function () {
    User::factory()->create(['email' => 'someone@jobtrek.ch', 'azure_id' => 'other-azure-id']);
    fakeGraph([GROUP_IT]);
    fakeSocialiteUser('azure-1', 'someone@jobtrek.ch');

    $this->get(route('microsoft.callback'))->assertRedirect(route('login'));

    $this->assertGuest();
    expect(User::where('azure_id', 'azure-1')->exists())->toBeFalse();
});

test('a coach losing the role releases their apprentices', function () {
    $coach = User::factory()->coach()->create(['azure_id' => 'azure-1']);
    $apprentice = User::factory()->apprentice()->create();
    $apprentice->forceFill(['coach_id' => $coach->id])->save();
    fakeGraph([GROUP_TRAINER]);
    fakeSocialiteUser();

    $this->get(route('microsoft.callback'));

    expect($coach->fresh()->role)->toBe(UserRole::Trainer)
        ->and($apprentice->fresh()->coach_id)->toBeNull();
});

test('the session check re-syncs the role', function () {
    $user = User::factory()->apprentice()->create(['azure_id' => 'azure-1']);
    fakeGraph([GROUP_TRAINER]);

    $this->actingAs($user)->get(route('profile.edit'))->assertOk();

    expect($user->fresh()->role)->toBe(UserRole::Trainer);
});

test('the session check logs out a user who lost their role', function () {
    $user = User::factory()->apprentice()->create(['azure_id' => 'azure-1']);
    fakeGraph([]);

    $this->actingAs($user)->get(route('profile.edit'))->assertRedirect(route('login'));

    $this->assertGuest();
    expect($user->fresh()->is_active)->toBeFalse();
});

test('the session check fails open on a Graph outage', function () {
    $user = User::factory()->apprentice()->create(['azure_id' => 'azure-1']);
    fakeGraph(null);

    $this->actingAs($user)->get(route('profile.edit'))->assertOk();

    $this->assertAuthenticatedAs($user);
});
