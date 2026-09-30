<?php

use App\Models\Apprenticeship;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

const IT_GROUP = 'group-it';
const TRAINER_GROUP = 'group-trainer';

beforeEach(function () {
    Cache::flush();
    config()->set('services.azure.group_roles', [
        IT_GROUP => 'apprentices_IT',
        TRAINER_GROUP => 'trainer',
    ]);
    Apprenticeship::query()->create(['name' => 'Informaticien·ne CFC']);
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

    $this->get(route('microsoft.callback'))->assertRedirect(route('home'));

    $user = User::where('azure_id', 'azure-2')->first();
    expect($user)->not->toBeNull()
        ->and($user->role->value)->toBe('apprentice');
    $this->assertAuthenticatedAs($user);
});

test('a trainer logs in with the IT apprenticeship and can view an IT apprentice grade', function () {
    fakeSso('azure-trainer', 'trainer@example.test', TRAINER_GROUP);

    $this->get(route('microsoft.callback'))->assertRedirect(route('home'));

    $trainer = User::where('azure_id', 'azure-trainer')->firstOrFail();
    expect($trainer->role->value)->toBe('trainer')
        ->and($trainer->apprenticeship->name)->toBe('Informaticien·ne CFC');

    $apprentice = makeApprentice($trainer->apprenticeship);
    $grade = makeGrade($apprentice);

    $this->get(route('grades.show', $grade))->assertOk();
    $this->get(route('apprentices.grades.show', [$apprentice, $grade]))->assertOk();
});
