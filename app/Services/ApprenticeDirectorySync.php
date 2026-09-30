<?php

namespace App\Services;

use App\Enums\AzureGroup;
use App\Enums\UserRole;
use App\Exceptions\ApprenticeSyncAbortedException;
use App\Models\Apprenticeship;
use App\Models\User;
use App\Services\Microsoft\MicrosoftGraphService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Pre-provisions apprentice accounts from the Entra apprentice groups, so the
 * apprentices list is complete before anybody has logged in, and deactivates
 * apprentices who are no longer valid. The Graph ids of the mapped groups are
 * diffed against the local `azure_id`s, with the sign-in rules: exactly one
 * mapped group (`AzureGroup::resolveFrom`), enabled account, no e-mail conflict.
 *
 * All Graph calls happen first, then every write runs in one transaction: a
 * missing group id or any Graph failure aborts without touching the database.
 * Only apprentices are deactivated; trainers are left to the sign-in re-check.
 */
class ApprenticeDirectorySync
{
    public function __construct(
        private readonly MicrosoftGraphService $graph,
        private readonly AzureAccountSync $sync,
    ) {}

    /**
     * @throws ApprenticeSyncAbortedException
     */
    public function run(): ApprenticeSyncResult
    {
        $apprenticeships = $this->apprenticeshipIds();
        [$members, $groupIdsByMember, $emptyGroups] = $this->fetchMembers();

        /** @var array<string, array{member: array<string, mixed>, group: AzureGroup}> $valid */
        $valid = [];

        foreach ($groupIdsByMember as $azureId => $groupIds) {
            $group = AzureGroup::resolveFrom($groupIds);

            if ($group === null) {
                MappingRolesService::warnIfAmbiguous($azureId, $groupIds);
            }

            if ($group?->role() === UserRole::Apprentice && ($members[$azureId]['accountEnabled'] ?? true) !== false) {
                $valid[$azureId] = ['member' => $members[$azureId], 'group' => $group];
            }
        }

        $guarded = array_map(fn (AzureGroup $g) => $apprenticeships[$g->apprenticeship()], $emptyGroups);

        return DB::transaction(fn (): ApprenticeSyncResult => new ApprenticeSyncResult(
            ...$this->upsert($valid),
            deactivated: $this->deactivateInvalid(array_keys($valid), $guarded, $emptyGroups),
        ));
    }

    /**
     * Seeded apprenticeship id by name, checked before any write.
     *
     * @return array<string, int>
     */
    private function apprenticeshipIds(): array
    {
        $ids = Apprenticeship::pluck('id', 'name')->map(fn ($id) => (int) $id)->all();

        foreach (AzureGroup::cases() as $group) {
            if (! isset($ids[$group->apprenticeship()])) {
                $this->abort(ApprenticeSyncAbortedException::apprenticeshipMissing($group->apprenticeship()));
            }
        }

        return $ids;
    }

    /**
     * Read the members of every mapped group, with every mapped group they are in; write nothing.
     *
     * @return array{
     *     0: array<string, array<string, mixed>>,
     *     1: array<string, list<string>>,
     *     2: list<AzureGroup>,
     * } member by azure id, group ids by azure id, apprentice groups with no member
     */
    private function fetchMembers(): array
    {
        $members = [];
        $groupIdsByMember = [];
        $emptyGroups = [];

        foreach (AzureGroup::cases() as $group) {
            $groupId = $group->groupId() ?? $this->abort(ApprenticeSyncAbortedException::groupNotConfigured($group));
            $groupMembers = $this->graph->getGroupMembers($groupId)
                ?? $this->abort(ApprenticeSyncAbortedException::graphFailed("members lookup for [{$group->value}]"));

            if ($groupMembers === [] && $group->role() === UserRole::Apprentice) {
                $emptyGroups[] = $group;
            }

            foreach ($groupMembers as $member) {
                $members[$member['id']] ??= $member;
                $groupIdsByMember[$member['id']][] = $groupId;
            }
        }

        return [$members, $groupIdsByMember, $emptyGroups];
    }

    /**
     * @param  array<string, array{member: array<string, mixed>, group: AzureGroup}>  $valid
     * @return array{created: int, skipped: int}
     */
    private function upsert(array $valid): array
    {
        $created = 0;
        $skipped = 0;
        $users = User::whereIn('azure_id', array_keys($valid))->get()->keyBy('azure_id');

        foreach ($valid as $azureId => ['member' => $member, 'group' => $group]) {
            $user = $users->get($azureId);

            if ($user !== null) {
                $this->sync->apply($user, $group);

                continue;
            }

            $email = $member['userPrincipalName'] ?? null;

            if (! is_string($email) || $email === '') {
                Log::warning('Apprentice sync: member without userPrincipalName skipped.', ['azure_id' => $azureId]);
                $skipped++;

                continue;
            }

            if (User::where('email', $email)->exists()) {
                Log::warning('Apprentice sync: email already belongs to another account, skipped.', ['azure_id' => $azureId]);
                $skipped++;

                continue;
            }

            $this->sync->apply($this->sync->newAccount((string) $azureId, ($member['displayName'] ?? null) ?: $email, $email), $group);
            $created++;
        }

        return ['created' => $created, 'skipped' => $skipped];
    }

    /**
     * Deactivate active apprentices that are not valid any more. An apprenticeship
     * whose group came back empty is left alone: that is far more likely a wrong
     * group id or a Graph hiccup than everybody leaving at once.
     *
     * @param  list<string|int>  $validIds
     * @param  list<int>  $guarded  apprenticeship ids
     * @param  list<AzureGroup>  $emptyGroups
     */
    private function deactivateInvalid(array $validIds, array $guarded, array $emptyGroups): int
    {
        foreach ($emptyGroups as $group) {
            Log::warning('Apprentice sync: group has no members, deactivations skipped.', ['group' => $group->value]);
        }

        return User::role(UserRole::Apprentice->value)
            ->where('is_active', true)
            ->whereNotNull('azure_id')
            ->whereNotIn('azure_id', array_map('strval', $validIds))
            ->whereNotIn('apprenticeship_id', $guarded)
            ->update(['is_active' => false]);
    }

    private function abort(ApprenticeSyncAbortedException $e): never
    {
        Log::error($e->getMessage());

        throw $e;
    }
}
