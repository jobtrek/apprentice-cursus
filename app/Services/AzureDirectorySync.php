<?php

namespace App\Services;

use App\Enums\AzureGroup;
use App\Exceptions\ApprenticeshipNotSeededException;
use App\Exceptions\AzureSyncFailedException;
use App\Models\User;
use App\Services\Microsoft\MicrosoftGraphService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * The account sync: copies the membership of every mapped Entra group into
 * local accounts. All or nothing: every group is fetched before anything is
 * written, so a partial Graph response can never deactivate a whole group.
 */
class AzureDirectorySync
{
    public function __construct(
        private readonly MicrosoftGraphService $graph,
        private readonly AzureAccountSync $sync,
    ) {}

    /**
     * @throws AzureSyncFailedException when a group is unconfigured or Graph fails; nothing is written
     * @throws ApprenticeshipNotSeededException when a section is missing; the run is rolled back
     */
    public function run(): AzureSyncResult
    {
        $members = $this->fetchMembers();

        $result = DB::transaction(fn (): AzureSyncResult => $this->apply($members));

        Log::info('Azure account sync finished.', $result->toArray());

        return $result;
    }

    /**
     * Enabled members of the mapped groups, keyed by azure id. Disabled
     * accounts count as in no group.
     *
     * @return array<string, array{groups: list<AzureGroup>, name: string, email: string|null}>
     *
     * @throws AzureSyncFailedException
     */
    private function fetchMembers(): array
    {
        $fetched = [];

        foreach (AzureGroup::cases() as $group) {
            $groupId = $group->groupId() ?? throw AzureSyncFailedException::groupNotConfigured($group->value);

            $fetched[$group->value] = $this->graph->getGroupMembers($groupId)
                ?? throw AzureSyncFailedException::membersLookupFailed($group->value);
        }

        $members = [];

        foreach (AzureGroup::cases() as $group) {
            foreach ($fetched[$group->value] as $member) {
                $azureId = $member['id'] ?? null;

                if (! is_string($azureId) || $azureId === '' || ($member['accountEnabled'] ?? true) === false) {
                    continue;
                }

                // Same field as the Socialite Azure provider uses for the login email.
                $email = $member['userPrincipalName'] ?? $member['mail'] ?? null;

                $members[$azureId]['groups'][] = $group;
                $members[$azureId]['email'] = is_string($email) && $email !== '' ? $email : null;
                $members[$azureId]['name'] = ($member['displayName'] ?? '') !== '' ? $member['displayName'] : (string) $email;
            }
        }

        return $members;
    }

    /**
     * @param  array<string, array{groups: list<AzureGroup>, name: string, email: string|null}>  $members
     */
    private function apply(array $members): AzureSyncResult
    {
        $created = $updated = $deactivated = $skipped = 0;
        $synced = [];
        $existing = User::whereNotNull('azure_id')->get()->keyBy('azure_id');
        $renamed = $this->moveEmails($members, $existing);

        foreach ($members as $azureId => $member) {
            if (count($member['groups']) > 1) {
                Log::warning('Azure account is in more than one role group, not mapped.', [
                    'azure_id' => $azureId,
                    'groups' => array_map(fn (AzureGroup $group) => $group->value, $member['groups']),
                ]);

                continue;
            }

            $group = $member['groups'][0];
            $user = $existing->get($azureId);

            if ($user === null) {
                if ($member['email'] === null || User::where('email', $member['email'])->exists()) {
                    Log::warning('Azure account sync skipped: email is missing or belongs to another account.', [
                        'azure_id' => $azureId,
                    ]);
                    $skipped++;

                    continue;
                }

                $user = new User([
                    'name' => $member['name'],
                    'email' => $member['email'],
                    'azure_id' => $azureId,
                    'tenant_id' => config('services.azure.tenant'),
                ]);
                $this->sync->apply($user, $group);
                $created++;
            } else {
                $before = [$user->name, $user->is_active, $user->apprenticeship_context_id, $user->role];

                if ($member['name'] !== '') {
                    $user->name = $member['name'];
                }

                $this->sync->apply($user, $group);

                if (isset($renamed[$azureId]) || $before !== [$user->name, $user->is_active, $user->apprenticeship_context_id, $user->role]) {
                    $updated++;
                }
            }

            $user->forceFill(['synced_at' => now()])->save();
            $synced[$azureId] = true;
        }

        // Local accounts (azure_id = null) are never in $existing, so never touched.
        foreach ($existing as $azureId => $user) {
            if ($user->is_active && ! isset($synced[$azureId])) {
                $this->sync->deactivate($user);
                $deactivated++;
            }
        }

        return new AzureSyncResult($created, $updated, $deactivated, $skipped);
    }

    /**
     * Follows UPN renames of existing accounts, so an old address is freed for
     * whoever gets it next. Runs before any account is created, so a newcomer
     * can take an address renamed away in the same run.
     *
     * Renames are applied together: accounts that exchange UPNs, or rename in a
     * chain, each take an address the other one is giving up. A rename onto an
     * address held by an account that keeps it is skipped (`users.email` is
     * unique), and so is any rename waiting on a skipped one.
     *
     * @param  array<string, array{groups: list<AzureGroup>, name: string, email: string|null}>  $members
     * @param  Collection<string, User>  $existing  keyed by azure id
     * @return array<string, true> azure ids whose email changed
     */
    private function moveEmails(array $members, Collection $existing): array
    {
        /** @var array<int, array{user: User, email: string}> $moves keyed by user id */
        $moves = [];

        foreach ($members as $azureId => $member) {
            $user = $existing->get($azureId);

            // Accounts in several groups are not synced, so their email is not either.
            if ($user === null || count($member['groups']) > 1 || $member['email'] === null || $member['email'] === $user->email) {
                continue;
            }

            $moves[$user->id] = ['user' => $user, 'email' => $member['email']];
        }

        if ($moves === []) {
            return [];
        }

        $holders = User::whereIn('email', array_column($moves, 'email'))->pluck('id', 'email');

        // Drop renames onto an address whose holder is not moving away, until none is left.
        do {
            $dropped = false;

            foreach ($moves as $userId => $move) {
                $holder = $holders->get($move['email']);

                if ($holder !== null && $holder !== $userId && ! isset($moves[$holder])) {
                    Log::warning('Azure account sync kept the old email: the new one belongs to another account.', [
                        'azure_id' => $move['user']->azure_id,
                    ]);
                    unset($moves[$userId]);
                    $dropped = true;
                }
            }
        } while ($dropped);

        // The unique index is checked per statement: park every moving address on a
        // placeholder first, so an exchange never holds the same address twice.
        foreach ($moves as $userId => $move) {
            $move['user']->forceFill(['email' => "{$userId}.email-rename@sync.invalid"])->save();
        }

        $renamed = [];

        foreach ($moves as $move) {
            $move['user']->forceFill(['email' => $move['email']])->save();
            $renamed[(string) $move['user']->azure_id] = true;
        }

        return $renamed;
    }
}
