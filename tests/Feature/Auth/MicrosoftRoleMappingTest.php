<?php

use App\Enums\UserRole;
use App\Models\Apprenticeship;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as AzureUser;

const AZURE_ID = '11111111-1111-1111-1111-111111111111';

beforeEach(function () {
    config()->set('services.azure.group_roles', [
        'group-it' => 'apprentices_IT',
        'group-ec' => 'apprentices_EC',
        'group-trainer' => 'trainer',
    ]);

    $azureUser = (new AzureUser)->map([
        'id' => AZURE_ID,
        'name' => 'Jane Doe',
        'email' => 'jane.doe@example.com',
    ]);

    Socialite::shouldReceive('driver->user')->andReturn($azureUser);
});

/** @param  list<string>|null  $groupIds  null fakes a Graph failure */
function fakeAzureGroups(?array $groupIds): void
{
    Http::fake([
        'login.microsoftonline.com/*' => Http::response(['access_token' => 'token']),
        'graph.microsoft.com/v1.0/users/*/transitiveMemberOf*' => $groupIds === null
            ? Http::response([], 500)
            : Http::response(['value' => array_map(fn (string $id) => ['id' => $id, 'displayName' => $id], $groupIds)]),
    ]);
}

it('creates a new user with the role and track of their group', function (string $groupId, UserRole $role, ?string $code) {
    fakeAzureGroups(['unrelated-group', $groupId]);

    $this->get('/auth/microsoft/callback')->assertRedirect(route('home'));

    $user = User::where('azure_id', AZURE_ID)->firstOrFail();

    expect($user->role)->toBe($role)
        ->and($user->apprenticeship_id)->toBe($code === null ? null : Apprenticeship::where('code', $code)->value('id'));

    $this->assertAuthenticatedAs($user);
})->with([
    'IT apprentice' => ['group-it', UserRole::Apprentice, 'it'],
    'EC apprentice' => ['group-ec', UserRole::Apprentice, 'ec'],
    'trainer' => ['group-trainer', UserRole::Trainer, null],
]);

it('updates the role of an existing user on login', function () {
    fakeAzureGroups(['group-trainer']);

    $user = User::create(['name' => 'Jane Doe', 'email' => 'jane.doe@example.com', 'azure_id' => AZURE_ID]);

    $this->get('/auth/microsoft/callback')->assertRedirect(route('home'));

    expect($user->fresh()->role)->toBe(UserRole::Trainer);
});

it('refuses the login when the account is in no mapped group', function () {
    fakeAzureGroups(['unrelated-group']);

    $this->get('/auth/microsoft/callback')->assertRedirect(route('login'));

    expect(User::where('azure_id', AZURE_ID)->exists())->toBeFalse();
    $this->assertGuest();
});

it('refuses the login when the account is in more than one role group', function () {
    fakeAzureGroups(['group-it', 'group-trainer']);

    $this->get('/auth/microsoft/callback')->assertRedirect(route('login'));

    $this->assertGuest();
});

it('refuses the login when Graph is unavailable', function () {
    fakeAzureGroups(null);

    $this->get('/auth/microsoft/callback')->assertRedirect(route('login'));

    expect(User::where('azure_id', AZURE_ID)->exists())->toBeFalse();
    $this->assertGuest();
});
