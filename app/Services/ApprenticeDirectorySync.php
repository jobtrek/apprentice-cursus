<?php

namespace App\Services;

use App\Enums\AzureGroup;
use App\Enums\UserRole;
use App\Exceptions\ApprenticeshipNotSeededException;
use App\Exceptions\ApprenticeSyncAbortedException;
use App\Models\Apprenticeship;
use App\Models\User;
use App\Services\Microsoft\MicrosoftGraphService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Pre-provisions apprentice accounts from the two Entra apprentice groups, so the
 * apprentices list is complete before anybody has logged in, and deactivates
 * apprentices who left the groups. Applies the same rules as the SSO login
 * (single mapped group, disabled account, e-mail conflict).
 *
 * All Graph calls happen first, then every write runs in one transaction: a
 * missing group id or any Graph failure aborts without touching the database.
 * Trainers are never touched: they are created at their first login.
 */
class ApprenticeDirectorySync
{
    private const GROUPS = [AzureGroup::ApprenticesIt, AzureGroup::ApprenticesEc];

    public function __construct(
        private readonly MicrosoftGraphService $graph,
        private readonly MappingRolesService $mappingRoles,
        private readonly AzureAccountSync $sync,
    ) {}

    /**
     * @throws ApprenticeSyncAbortedException
     */
    public function run(): ApprenticeSyncResult
    {
        [$upserts, $deactivations, $memberIds, $emptyGroups, $skipped] = $this->collect();

        try {
            return DB::transaction(
                fn (): ApprenticeSyncResult => $this->write($upserts, $deactivations, $memberIds, $emptyGroups, $skipped),
            );
        } catch (ApprenticeshipNotSeededException $e) {
            Log::error('Apprentice sync aborted: apprenticeship is not seeded.', ['apprenticeship' => $e->apprenticeship]);

            throw ApprenticeSyncAbortedException::apprenticeshipMissing($e->apprenticeship, $e);
        }
    }

    /**
     * Phase 1: read Entra ID, write nothing.
     *
     * @return array{
     *     0: array<string, array{member: array{id: string, displayName?: string|null, userPrincipalName?: string|null}, group: AzureGroup}>,
     *     1: list<string>,
     *     2: list<string>,
     *     3: list<AzureGroup>,
     *     4: int,
     * } upserts by azure id, azure ids to deactivate if they exist locally, every member id, groups with no member, skipped count
     */
    private function collect(): array
    {
        $members = [];
        $emptyGroups = [];

        foreach (self::GROUPS as $group) {
            $groupId = $group->groupId();

            if ($groupId === null) {
                $this->abort(ApprenticeSyncAbortedException::groupNotConfigured($group));
            }

            $groupMembers = $this->graph->getGroupMembers($groupId);

            if ($groupMembers === null) {
                $this->abort(ApprenticeSyncAbortedException::graphFailed("members lookup for [{$group->value}]"));
            }

            if ($groupMembers === []) {
                $emptyGroups[] = $group;
            }

            foreach ($groupMembers as $member) {
                $members[$member['id']] ??= $member;
            }
        }

        $upserts = [];
        $deactivations = [];
        $skipped = 0;

        foreach ($members as $id => $member) {
            if (($member['accountEnabled'] ?? true) === false) {
                $deactivations[] = $id;

                continue;
            }

            try {
                $group = $this->mappingRoles->resolveGroup($id);
            } catch (RuntimeException $e) {
                $this->abort(ApprenticeSyncAbortedException::graphFailed("group lookup for [{$id}]", $e), $e);
            }

            if ($group === null) {
                $deactivations[] = $id;

                continue;
            }

            if ($group->role() !== UserRole::Apprentice) {
                Log::warning('Apprentice sync: member resolved to a non-apprentice group, skipped.', [
                    'azure_id' => $id,
                    'group' => $group->value,
                ]);
                $skipped++;

                continue;
            }

            $upserts[$id] = ['member' => $member, 'group' => $group];
        }

        return [$upserts, $deactivations, array_map('strval', array_keys($members)), $emptyGroups, $skipped];
    }

    /**
     * Phase 2: apply the plan in the caller's transaction.
     *
     * @param  array<string, array{member: array{id: string, displayName?: string|null, userPrincipalName?: string|null}, group: AzureGroup}>  $upserts
     * @param  list<string>  $deactivations
     * @param  list<string>  $memberIds
     * @param  list<AzureGroup>  $emptyGroups
     */
    private function write(array $upserts, array $deactivations, array $memberIds, array $emptyGroups, int $skipped): ApprenticeSyncResult
    {
        $created = 0;
        $updated = 0;
        $deactivated = 0;

        foreach ($upserts as $azureId => ['member' => $member, 'group' => $group]) {
            $user = User::where('azure_id', $azureId)->first();
            $email = $member['userPrincipalName'] ?? null;

            if ($user === null) {
                if ($email === null || $email === '') {
                    Log::warning('Apprentice sync: member without userPrincipalName skipped.', ['azure_id' => $azureId]);
                    $skipped++;

                    continue;
                }

                if (User::where('email', $email)->exists()) {
                    Log::warning('Apprentice sync: email already belongs to another account, skipped.', ['azure_id' => $azureId]);
                    $skipped++;

                    continue;
                }

                $this->sync->apply($this->sync->newAccount($azureId, ($member['displayName'] ?? null) ?: $email, $email), $group);
                $created++;

                continue;
            }

            $before = [$user->is_active, $user->apprenticeship_id, $user->role];
            $this->sync->apply($user, $group);

            if ($before !== [$user->is_active, $user->apprenticeship_id, $user->role]) {
                $updated++;
            }
        }

        foreach ($deactivations as $azureId) {
            $user = User::where('azure_id', $azureId)->first();

            if ($user !== null && $user->is_active) {
                $this->sync->deactivate($user);
                $deactivated++;
            }
        }

        return new ApprenticeSyncResult($created, $updated, $deactivated + $this->deactivateRemoved($memberIds, $emptyGroups), $skipped);
    }

    /**
     * Deactivate active apprentices that are in no apprentice group any more. An
     * apprenticeship whose group came back empty is left alone: that is far more
     * likely a wrong group id or a Graph hiccup than everybody leaving at once.
     *
     * @param  list<string>  $memberIds
     * @param  list<AzureGroup>  $emptyGroups
     */
    private function deactivateRemoved(array $memberIds, array $emptyGroups): int
    {
        $guarded = Apprenticeship::whereIn('name', array_map(fn (AzureGroup $g) => $g->apprenticeship(), $emptyGroups))
            ->pluck('id')
            ->all();

        $deactivated = 0;

        $candidates = User::role(UserRole::Apprentice->value)
            ->whereNotNull('azure_id')
            ->where('is_active', true)
            ->whereNotIn('azure_id', $memberIds)
            ->get();

        foreach ($candidates as $user) {
            if (in_array($user->apprenticeship_id, $guarded, true)) {
                Log::warning('Apprentice sync: group has no members, removal deactivation skipped.', ['user_id' => $user->id]);

                continue;
            }

            $this->sync->deactivate($user);
            $deactivated++;
        }

        return $deactivated;
    }

    private function abort(ApprenticeSyncAbortedException $e, ?RuntimeException $cause = null): never
    {
        Log::error($e->getMessage(), $cause === null ? [] : ['message' => $cause->getMessage()]);

        throw $e;
    }
}
