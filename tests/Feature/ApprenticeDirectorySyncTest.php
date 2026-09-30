<?php

use App\Enums\AzureGroup;
use App\Enums\UserRole;
use App\Exceptions\ApprenticeSyncAbortedException;
use App\Models\Apprenticeship;
use App\Models\User;
use App\Services\ApprenticeDirectorySync;
use App\Services\ApprenticeSyncResult;
use Database\Seeders\ApprenticeshipSeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

const SYNC_IT = 'group-it';
const SYNC_EC = 'group-ec';
const SYNC_TRAINER = 'group-trainer';

beforeEach(function () {
    Cache::flush();
    config()->set('services.azure.tenant', 'tenant-1');
    config()->set('services.azure.groups', [
        AzureGroup::ApprenticesIt->value => SYNC_IT,
        AzureGroup::ApprenticesEc->value => SYNC_EC,
        AzureGroup::Trainer->value => SYNC_TRAINER,
    ]);
    $this->it = Apprenticeship::query()->create(['name' => ApprenticeshipSeeder::IT]);
    $this->ec = Apprenticeship::query()->create(['name' => ApprenticeshipSeeder::EC]);
});

/** @return array<string, mixed> */
function syncMember(string $id, ?string $upn = null, bool $enabled = true, ?string $name = null): array
{
    return [
        '@odata.type' => '#microsoft.graph.user',
        'id' => $id,
        'displayName' => $name ?? "Name {$id}",
        'userPrincipalName' => $upn ?? "{$id}@example.test",
        'accountEnabled' => $enabled,
    ];
}

/**
 * Fake the token, members and transitiveMemberOf endpoints.
 *
 * @param  array<string, list<array<string, mixed>>|int>  $members  group id => members, or an HTTP status to fail with
 * @param  array<string, list<string>|int>  $memberOf  azure id => group ids, or an HTTP status; defaults to the group(s) the member is listed in
 */
function fakeDirectory(array $members, array $memberOf = []): void
{
    Http::fake(function (Request $request) use ($members, $memberOf) {
        $url = $request->url();

        if (str_contains($url, 'login.microsoftonline.com')) {
            return Http::response(['access_token' => 'token']);
        }

        if (preg_match('#/groups/([^/]+)/transitiveMembers#', $url, $m)) {
            $entry = $members[$m[1]] ?? [];

            return is_int($entry) ? Http::response([], $entry) : Http::response(['value' => $entry]);
        }

        if (preg_match('#/users/([^/]+)/transitiveMemberOf#', $url, $m)) {
            $entry = $memberOf[$m[1]] ?? array_keys(array_filter(
                $members,
                fn ($list) => is_array($list) && in_array($m[1], array_column($list, 'id'), true),
            ));

            return is_int($entry)
                ? Http::response([], $entry)
                : Http::response(['value' => array_map(fn (string $id) => ['id' => $id, 'displayName' => 'g'], $entry)]);
        }

        return Http::response([], 404);
    });
}

function runSync(): ApprenticeSyncResult
{
    return app(ApprenticeDirectorySync::class)->run();
}

function syncedApprentice(string $azureId, int $apprenticeshipId, bool $active = true, array $attributes = []): User
{
    return User::factory()->create([
        'azure_id' => $azureId,
        'apprenticeship_id' => $apprenticeshipId,
        'is_active' => $active,
        ...$attributes,
    ]);
}

test('it creates missing apprentices with role, apprenticeship and identity', function () {
    fakeDirectory([
        SYNC_IT => [syncMember('a1', 'anna@example.test', name: 'Anna')],
        SYNC_EC => [syncMember('b1', 'ben@example.test', name: 'Ben')],
    ]);

    $result = runSync();

    expect($result->created)->toBe(2);

    $anna = User::where('azure_id', 'a1')->firstOrFail();
    expect($anna->name)->toBe('Anna')
        ->and($anna->email)->toBe('anna@example.test')
        ->and($anna->tenant_id)->toBe('tenant-1')
        ->and($anna->role)->toBe(UserRole::Apprentice)
        ->and($anna->apprenticeship_id)->toBe($this->it->id)
        ->and($anna->is_active)->toBeTrue()
        ->and(User::where('azure_id', 'b1')->firstOrFail()->apprenticeship_id)->toBe($this->ec->id);
});

test('it falls back to the principal name and ignores non-user members', function () {
    fakeDirectory([
        SYNC_IT => [
            ['@odata.type' => '#microsoft.graph.user', 'id' => 'a1', 'displayName' => '', 'userPrincipalName' => 'anna@example.test'],
            ['@odata.type' => '#microsoft.graph.group', 'id' => 'nested', 'displayName' => 'Nested'],
        ],
        SYNC_EC => [],
    ]);

    $result = runSync();

    expect($result->created)->toBe(1)
        ->and(User::where('azure_id', 'a1')->firstOrFail()->name)->toBe('anna@example.test')
        ->and(User::where('azure_id', 'nested')->exists())->toBeFalse();
});

test('it follows paging of the members endpoint', function () {
    Http::fake(function (Request $request) {
        $url = $request->url();

        if (str_contains($url, 'login.microsoftonline.com')) {
            return Http::response(['access_token' => 'token']);
        }

        if (str_contains($url, 'page=2')) {
            return Http::response(['value' => [syncMember('a2')]]);
        }

        if (preg_match('#/groups/'.SYNC_IT.'/transitiveMembers#', $url)) {
            return Http::response([
                'value' => [syncMember('a1')],
                '@odata.nextLink' => 'https://graph.microsoft.com/v1.0/groups/'.SYNC_IT.'/transitiveMembers?page=2',
            ]);
        }

        if (str_contains($url, '/groups/'.SYNC_EC)) {
            return Http::response(['value' => []]);
        }

        return Http::response(['value' => [['id' => SYNC_IT]]]);
    });

    expect(runSync()->created)->toBe(2)
        ->and(User::whereIn('azure_id', ['a1', 'a2'])->count())->toBe(2);
});

test('it updates existing apprentices without overwriting name or email and reactivates them', function () {
    $active = syncedApprentice('a1', $this->it->id, attributes: ['name' => 'Local name', 'email' => 'local@example.test']);
    $inactive = syncedApprentice('a2', $this->it->id, false, ['name' => 'Old', 'email' => 'old@example.test']);
    fakeDirectory([
        SYNC_IT => [syncMember('a1', 'new1@example.test', name: 'Entra name'), syncMember('a2', 'new2@example.test')],
        SYNC_EC => [],
    ]);

    $result = runSync();

    expect($result->created)->toBe(0)
        ->and($result->updated)->toBe(1)
        ->and($active->fresh()->name)->toBe('Local name')
        ->and($active->fresh()->email)->toBe('local@example.test')
        ->and($inactive->fresh()->is_active)->toBeTrue()
        ->and($inactive->fresh()->email)->toBe('old@example.test');
});

test('it deactivates apprentices removed from every apprentice group', function () {
    $stays = syncedApprentice('a1', $this->it->id);
    $left = syncedApprentice('a2', $this->it->id);
    fakeDirectory([SYNC_IT => [syncMember('a1')], SYNC_EC => []]);

    $result = runSync();

    expect($result->deactivated)->toBe(1)
        ->and($left->fresh()->is_active)->toBeFalse()
        ->and($stays->fresh()->is_active)->toBeTrue()
        ->and($left->fresh())->not->toBeNull();
});

test('it skips accounts whose email belongs to another account and continues', function () {
    $other = User::factory()->create(['email' => 'taken@example.test', 'azure_id' => null, 'name' => 'Other']);
    fakeDirectory([
        SYNC_IT => [syncMember('a1', 'taken@example.test'), syncMember('a2', 'fine@example.test')],
        SYNC_EC => [],
    ]);

    $result = runSync();

    expect($result->created)->toBe(1)
        ->and($result->skipped)->toBe(1)
        ->and(User::where('azure_id', 'a1')->exists())->toBeFalse()
        ->and(User::where('azure_id', 'a2')->exists())->toBeTrue()
        ->and($other->fresh()->name)->toBe('Other')
        ->and($other->fresh()->azure_id)->toBeNull();
});

test('a member of several mapped groups is not created and an existing account is deactivated', function () {
    $existing = syncedApprentice('a1', $this->it->id);
    fakeDirectory(
        [SYNC_IT => [syncMember('a1'), syncMember('a2'), syncMember('a3')], SYNC_EC => [syncMember('a1'), syncMember('a2')]],
        ['a1' => [SYNC_IT, SYNC_EC], 'a2' => [SYNC_IT, SYNC_EC], 'a3' => [SYNC_IT]],
    );

    $result = runSync();

    expect($result->created)->toBe(1)
        ->and($existing->fresh()->is_active)->toBeFalse()
        ->and(User::where('azure_id', 'a2')->exists())->toBeFalse()
        ->and(User::where('azure_id', 'a3')->exists())->toBeTrue();
});

test('disabled Entra accounts are deactivated or never created', function () {
    $existing = syncedApprentice('a1', $this->it->id);
    fakeDirectory([
        SYNC_IT => [syncMember('a1', enabled: false), syncMember('a2', enabled: false), syncMember('a3')],
        SYNC_EC => [],
    ]);

    $result = runSync();

    expect($result->created)->toBe(1)
        ->and($result->deactivated)->toBe(1)
        ->and($existing->fresh()->is_active)->toBeFalse()
        ->and(User::where('azure_id', 'a2')->exists())->toBeFalse();
});

test('it never touches trainers', function () {
    $trainer = User::factory()->trainer()->create(['azure_id' => 't1', 'apprenticeship_id' => $this->it->id]);
    fakeDirectory([SYNC_IT => [syncMember('a1')], SYNC_EC => []]);

    runSync();

    expect($trainer->fresh()->is_active)->toBeTrue()
        ->and($trainer->fresh()->role)->toBe(UserRole::Trainer);
});

test('it aborts without writing when a group id is not configured', function () {
    config()->set('services.azure.groups.'.AzureGroup::ApprenticesEc->value, null);
    $existing = syncedApprentice('a2', $this->it->id);
    fakeDirectory([SYNC_IT => [syncMember('a1')]]);

    expect(fn () => runSync())->toThrow(ApprenticeSyncAbortedException::class);

    expect(User::where('azure_id', 'a1')->exists())->toBeFalse()
        ->and($existing->fresh()->is_active)->toBeTrue();
});

test('it aborts without writing when the second members call fails', function () {
    $existing = syncedApprentice('a2', $this->it->id);
    fakeDirectory([SYNC_IT => [syncMember('a1')], SYNC_EC => 500]);

    expect(fn () => runSync())->toThrow(ApprenticeSyncAbortedException::class);

    expect(User::where('azure_id', 'a1')->exists())->toBeFalse()
        ->and($existing->fresh()->is_active)->toBeTrue();
});

test('it aborts without writing when a member group lookup fails', function () {
    $existing = syncedApprentice('a3', $this->it->id);
    fakeDirectory([SYNC_IT => [syncMember('a1'), syncMember('a2')], SYNC_EC => []], ['a1' => [SYNC_IT], 'a2' => 500]);

    expect(fn () => runSync())->toThrow(ApprenticeSyncAbortedException::class);

    expect(User::where('azure_id', 'a1')->exists())->toBeFalse()
        ->and($existing->fresh()->is_active)->toBeTrue();
});

test('it rolls back when an apprenticeship is not seeded', function () {
    $this->ec->delete();
    fakeDirectory([SYNC_IT => [syncMember('a1')], SYNC_EC => [syncMember('b1')]]);

    expect(fn () => runSync())->toThrow(ApprenticeSyncAbortedException::class);

    expect(User::whereIn('azure_id', ['a1', 'b1'])->count())->toBe(0);
});

test('zero-member guard leaves the empty group apprenticeship alone', function () {
    Log::spy();
    $it = syncedApprentice('a1', $this->it->id);
    $ec = syncedApprentice('b1', $this->ec->id);
    fakeDirectory([SYNC_IT => [syncMember('a2')], SYNC_EC => []]);

    $result = runSync();

    expect($result->deactivated)->toBe(1)
        ->and($it->fresh()->is_active)->toBeFalse()
        ->and($ec->fresh()->is_active)->toBeTrue();

    Log::shouldHaveReceived('warning')->withArgs(fn ($message) => str_contains($message, 'group has no members'))->once();
});

test('the command reports counts and exits with success', function () {
    fakeDirectory([SYNC_IT => [syncMember('a1')], SYNC_EC => []]);

    $this->artisan('apprentices:sync')
        ->expectsOutputToContain('1 created')
        ->assertExitCode(0);
});

test('the command exits with failure when the sync aborts', function () {
    fakeDirectory([SYNC_IT => 500, SYNC_EC => []]);

    $this->artisan('apprentices:sync')->assertExitCode(1);

    expect(User::count())->toBe(0);
});

test('the command is scheduled daily', function () {
    $events = collect(app(Schedule::class)->events())
        ->filter(fn ($event) => str_contains($event->command, 'apprentices:sync'));

    expect($events)->toHaveCount(1)
        ->and($events->first()->expression)->toBe('0 0 * * *');
});
