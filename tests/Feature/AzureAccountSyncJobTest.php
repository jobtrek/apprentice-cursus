<?php

use App\Enums\UserRole;
use App\Exceptions\AzureSyncFailedException;
use App\Jobs\SyncAzureAccounts;
use App\Models\Apprenticeship;
use App\Models\User;
use App\Services\AzureDirectorySync;
use Database\Seeders\ApprenticeshipSeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

const SYNC_GROUPS = [
    'apprentices_IT' => 'g-it',
    'apprentices_EC' => 'g-ec',
    'trainer' => 'g-trainer',
    'coach' => 'g-coach',
];

beforeEach(function () {
    Cache::flush();
    config()->set('services.azure.groups', SYNC_GROUPS);
    $this->it = Apprenticeship::query()->create(['name' => ApprenticeshipSeeder::IT]);
    $this->ec = Apprenticeship::query()->create(['name' => ApprenticeshipSeeder::EC]);
});

/**
 * @param  array<string, list<array<string, mixed>>|int>  $members  group id => members, or an HTTP status for a failing group
 */
function fakeDirectory(array $members): void
{
    Http::swap(new HttpFactory);
    $fakes = ['login.microsoftonline.com/*' => Http::response(['access_token' => 'token'])];

    foreach (SYNC_GROUPS as $groupId) {
        $value = $members[$groupId] ?? [];
        $fakes["graph.microsoft.com/v1.0/groups/{$groupId}/transitiveMembers/*"] = is_int($value)
            ? Http::response([], $value)
            : Http::response(['value' => $value]);
    }

    Http::fake($fakes);
}

/** @return array<string, mixed> */
function member(string $id, ?string $name = null, bool $enabled = true): array
{
    return [
        'id' => $id,
        'displayName' => $name ?? "Name {$id}",
        'mail' => null,
        'userPrincipalName' => "{$id}@example.test",
        'accountEnabled' => $enabled,
    ];
}

test('it creates a user for each mapped group with the right role and section', function () {
    fakeDirectory([
        'g-it' => [member('a-it')],
        'g-ec' => [member('a-ec')],
        'g-trainer' => [member('a-trainer')],
        'g-coach' => [member('a-coach')],
    ]);

    $this->artisan('azure:sync')->assertExitCode(0);

    $it = User::where('azure_id', 'a-it')->firstOrFail();
    expect($it->role)->toBe(UserRole::Apprentice)
        ->and($it->apprenticeship_id)->toBe($this->it->id)
        ->and($it->email)->toBe('a-it@example.test')
        ->and($it->name)->toBe('Name a-it')
        ->and($it->is_active)->toBeTrue()
        ->and($it->synced_at)->not->toBeNull();
    expect(User::where('azure_id', 'a-ec')->firstOrFail()->apprenticeship_id)->toBe($this->ec->id);

    $trainer = User::where('azure_id', 'a-trainer')->firstOrFail();
    expect($trainer->role)->toBe(UserRole::Trainer)
        ->and($trainer->apprenticeship_id)->toBe($this->it->id);

    $coach = User::where('azure_id', 'a-coach')->firstOrFail();
    expect($coach->role)->toBe(UserRole::Coach)
        ->and($coach->apprenticeship_id)->toBeNull()
        ->and($coach->synced_at)->not->toBeNull();
});

test('it updates the role and name of an existing user and keeps the section on a section change', function () {
    Log::spy();
    $user = User::factory()->create(['azure_id' => 'a-1', 'name' => 'Old']);
    $user->forceFill(['apprenticeship_id' => $this->ec->id])->save();
    fakeDirectory(['g-it' => [member('a-1', 'New Name')]]);

    $this->artisan('azure:sync')->assertExitCode(0);

    $user = $user->fresh();
    expect($user->name)->toBe('New Name')
        ->and($user->apprenticeship_id)->toBe($this->ec->id)
        ->and($user->synced_at)->not->toBeNull();
    Log::shouldHaveReceived('warning')
        ->with('Microsoft SSO: section change pending confirmation, apprenticeship kept.', Mockery::type('array'))
        ->once();

    fakeDirectory(['g-trainer' => [member('a-1', 'New Name')]]);
    $this->artisan('azure:sync')->assertExitCode(0);

    expect($user->fresh()->role)->toBe(UserRole::Trainer);
});

test('an account in several groups is not created', function () {
    fakeDirectory(['g-it' => [member('a-2')], 'g-trainer' => [member('a-2')]]);

    $this->artisan('azure:sync')->assertExitCode(0);

    $this->assertDatabaseMissing('users', ['azure_id' => 'a-2']);
});

test('an account in several groups is deactivated when it already exists', function () {
    $user = User::factory()->create(['azure_id' => 'a-3']);
    fakeDirectory(['g-it' => [member('a-3')], 'g-coach' => [member('a-3')]]);

    $this->artisan('azure:sync')->assertExitCode(0);

    expect($user->fresh()->is_active)->toBeFalse();
});

test('a disabled member is not created and an existing one is deactivated', function () {
    $user = User::factory()->create(['azure_id' => 'a-4']);
    fakeDirectory(['g-it' => [member('a-4', enabled: false), member('a-5', enabled: false)]]);

    $this->artisan('azure:sync')->assertExitCode(0);

    expect($user->fresh()->is_active)->toBeFalse();
    $this->assertDatabaseMissing('users', ['azure_id' => 'a-5']);
});

test('an account missing from every group is deactivated and reactivated when it is back', function () {
    $user = User::factory()->create(['azure_id' => 'a-6']);
    fakeDirectory([]);

    $this->artisan('azure:sync')->assertExitCode(0);

    expect($user->fresh()->is_active)->toBeFalse();

    fakeDirectory(['g-it' => [member('a-6')]]);
    $this->artisan('azure:sync')->assertExitCode(0);

    expect($user->fresh()->is_active)->toBeTrue();
});

test('a local account without azure id is never deactivated', function () {
    $local = User::factory()->create(['azure_id' => null]);
    fakeDirectory([]);

    $this->artisan('azure:sync')->assertExitCode(0);

    expect($local->fresh()->is_active)->toBeTrue();
});

test('an email conflict creates no row and leaves the other account untouched', function () {
    $other = User::factory()->create(['email' => 'a-7@example.test', 'azure_id' => null]);
    fakeDirectory(['g-it' => [member('a-7')]]);

    $this->artisan('azure:sync')
        ->expectsOutputToContain('1 skipped')
        ->assertExitCode(0);

    $this->assertDatabaseMissing('users', ['azure_id' => 'a-7']);
    $other = $other->fresh();
    expect($other->azure_id)->toBeNull()
        ->and($other->is_active)->toBeTrue()
        ->and($other->synced_at)->toBeNull();
});

test('it follows a UPN rename and frees the old email', function () {
    $user = User::factory()->create(['azure_id' => 'a-10', 'email' => 'old@example.test']);
    fakeDirectory(['g-it' => [member('a-10')]]);

    $this->artisan('azure:sync')->assertExitCode(0);

    expect($user->fresh()->email)->toBe('a-10@example.test');
    $this->assertDatabaseMissing('users', ['email' => 'old@example.test']);
});

test('a UPN rename onto an email held by another account keeps the old email', function () {
    User::factory()->create(['email' => 'a-11@example.test', 'azure_id' => null]);
    $user = User::factory()->create(['azure_id' => 'a-11', 'email' => 'old@example.test']);
    fakeDirectory(['g-it' => [member('a-11')]]);

    $this->artisan('azure:sync')->assertExitCode(0);

    $user = $user->fresh();
    expect($user->email)->toBe('old@example.test')
        ->and($user->is_active)->toBeTrue();
});

test('two accounts that exchange UPNs exchange emails', function () {
    $first = User::factory()->create(['azure_id' => 'a-13', 'email' => 'a-14@example.test']);
    $second = User::factory()->create(['azure_id' => 'a-14', 'email' => 'a-13@example.test']);
    fakeDirectory(['g-it' => [member('a-13'), member('a-14')]]);

    $this->artisan('azure:sync')->assertExitCode(0);

    expect($first->fresh()->email)->toBe('a-13@example.test')
        ->and($second->fresh()->email)->toBe('a-14@example.test');
});

test('a newcomer takes an address renamed away in the same run', function () {
    $renamed = User::factory()->create(['azure_id' => 'a-15', 'email' => 'a-16@example.test']);
    fakeDirectory(['g-it' => [member('a-15'), member('a-16')]]);

    $this->artisan('azure:sync')->assertExitCode(0);

    expect($renamed->fresh()->email)->toBe('a-15@example.test');
    $this->assertDatabaseHas('users', ['azure_id' => 'a-16', 'email' => 'a-16@example.test']);
});

test('a rename waiting on a blocked rename keeps the old email', function () {
    User::factory()->create(['email' => 'a-17@example.test', 'azure_id' => null]);
    $blocked = User::factory()->create(['azure_id' => 'a-17', 'email' => 'a-18@example.test']);
    $waiting = User::factory()->create(['azure_id' => 'a-18', 'email' => 'old-18@example.test']);
    fakeDirectory(['g-it' => [member('a-17'), member('a-18')]]);

    $this->artisan('azure:sync')->assertExitCode(0);

    expect($blocked->fresh()->email)->toBe('a-18@example.test')
        ->and($waiting->fresh()->email)->toBe('old-18@example.test');
});

test('a member page without a value list fails the sync without changes', function () {
    $stale = User::factory()->create(['azure_id' => 'a-12']);
    Http::fake([
        'login.microsoftonline.com/*' => Http::response(['access_token' => 'token']),
        'graph.microsoft.com/v1.0/groups/g-trainer/transitiveMembers/*' => Http::response(['unexpected' => true]),
        'graph.microsoft.com/*' => Http::response(['value' => [member('a-12')]]),
    ]);

    expect(fn () => app(AzureDirectorySync::class)->run())
        ->toThrow(AzureSyncFailedException::class);

    expect($stale->fresh()->is_active)->toBeTrue();
});

test('it follows @odata.nextLink paging', function () {
    Http::fake([
        'login.microsoftonline.com/*' => Http::response(['access_token' => 'token']),
        'graph.microsoft.com/v1.0/groups/g-it/transitiveMembers/*' => Http::sequence()
            ->push(['value' => [member('p-1')], '@odata.nextLink' => 'https://graph.microsoft.com/page2'])
            ->push(['value' => [member('p-2')]]),
        'graph.microsoft.com/page2' => Http::response(['value' => [member('p-2')]]),
        'graph.microsoft.com/*' => Http::response(['value' => []]),
    ]);

    $this->artisan('azure:sync')->assertExitCode(0);

    $this->assertDatabaseHas('users', ['azure_id' => 'p-1']);
    $this->assertDatabaseHas('users', ['azure_id' => 'p-2']);
});

test('it is all or nothing when a group lookup fails', function () {
    $stale = User::factory()->create(['azure_id' => 'a-8']);
    fakeDirectory(['g-it' => [member('a-9')], 'g-trainer' => 503]);

    expect(fn () => app(AzureDirectorySync::class)->run())
        ->toThrow(AzureSyncFailedException::class);

    $this->assertDatabaseMissing('users', ['azure_id' => 'a-9']);
    expect($stale->fresh()->is_active)->toBeTrue();
});

test('an unconfigured group id fails the sync without changes', function () {
    config()->set('services.azure.groups.coach', null);
    $stale = User::factory()->create(['azure_id' => 'a-10']);
    fakeDirectory(['g-it' => [member('a-11')]]);

    expect(fn () => app(AzureDirectorySync::class)->run())
        ->toThrow(AzureSyncFailedException::class);

    $this->assertDatabaseMissing('users', ['azure_id' => 'a-11']);
    expect($stale->fresh()->is_active)->toBeTrue();
});

test('the command exits non-zero on a Graph failure', function () {
    fakeDirectory(['g-coach' => 500]);

    $this->artisan('azure:sync')->assertExitCode(1);
});

test('the command prints the counts', function () {
    fakeDirectory(['g-it' => [member('c-1')]]);

    $this->artisan('azure:sync')
        ->expectsOutputToContain('1 created, 0 updated, 0 deactivated, 0 skipped')
        ->assertExitCode(0);
});

test('the job runs the sync', function () {
    fakeDirectory(['g-it' => [member('j-1')]]);

    SyncAzureAccounts::dispatchSync();

    $this->assertDatabaseHas('users', ['azure_id' => 'j-1']);
});

test('the job is scheduled daily, unique and retried with backoff', function () {
    $event = collect(app(Schedule::class)->events())
        ->first(fn ($event) => str_contains($event->description ?? '', SyncAzureAccounts::class));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('0 0 * * *');

    $job = new SyncAzureAccounts;
    expect($job)->toBeInstanceOf(ShouldBeUnique::class)
        ->and($job->tries)->toBe(3)
        ->and($job->backoff)->toBe([60, 300]);
});
