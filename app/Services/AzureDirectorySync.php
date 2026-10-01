<?php

namespace App\Services;

use App\Enums\AzureGroup;
use App\Exceptions\ApprenticeshipNotSeededException;
use App\Exceptions\AzureSyncFailedException;
use App\Models\User;
use App\Services\Microsoft\MicrosoftGraphService;
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
                $before = [$user->name, $user->is_active, $user->apprenticeship_id, $user->role];

                if ($member['name'] !== '') {
                    $user->name = $member['name'];
                }

                $this->sync->apply($user, $group);

                if ($before !== [$user->name, $user->is_active, $user->apprenticeship_id, $user->role]) {
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
}
