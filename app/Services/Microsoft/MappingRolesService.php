<?php

namespace App\Services\Microsoft;

use App\Enums\UserRole;
use App\Models\Apprenticeship;
use Illuminate\Support\Facades\Log;

/**
 * Maps Entra group membership to the app's roles. The group Object IDs are configured in
 * config('services.azure.group_roles') as groupId => key; ROLE_GROUPS gives each key its
 * role and section.
 */
class MappingRolesService
{
    /**
     * The trainer group (it@jobtrek.ch) has no IT/EC split yet: every trainer is an IT
     * trainer until an EC trainer group exists in Entra.
     *
     * @var array<string, array{UserRole, string|null}>
     */
    private const ROLE_GROUPS = [
        'apprentices_IT' => [UserRole::Apprentice, Apprenticeship::IT],
        'apprentices_EC' => [UserRole::Apprentice, Apprenticeship::EC],
        'trainer' => [UserRole::Trainer, Apprenticeship::IT],
        'coach' => [UserRole::Coach, null],
    ];

    public function __construct(private readonly AzureGraphService $graph) {}

    /**
     * Resolve the role of one Entra user from their (transitive) group memberships.
     *
     * Returns null when the user has no role group, or more than one (logged): both
     * refuse access.
     *
     * @throws GraphUnavailableException
     */
    public function forUser(string $azureId): ?RoleAssignment
    {
        $groups = $this->graph->getGroups($azureId);

        if ($groups === null) {
            throw new GraphUnavailableException("Could not read the groups of Entra user {$azureId}.");
        }

        $groupIds = array_values(array_filter(array_column($groups, 'id')));

        return $this->fromGroupIds($azureId, $groupIds);
    }

    /**
     * @param  list<string>  $groupIds
     */
    public function fromGroupIds(string $azureId, array $groupIds): ?RoleAssignment
    {
        /** @var array<string, string> $configured */
        $configured = config('services.azure.group_roles');

        $keys = array_values(array_unique(array_filter(
            array_map(fn (string $id) => $configured[$id] ?? null, $groupIds),
        )));

        if ($keys === []) {
            Log::info('Entra user has no role group.', ['azure_id' => $azureId]);

            return null;
        }

        if (count($keys) > 1) {
            Log::warning('Entra user is in more than one role group; access refused.', [
                'azure_id' => $azureId,
                'groups' => $keys,
            ]);

            return null;
        }

        [$role, $apprenticeshipCode] = self::ROLE_GROUPS[$keys[0]];

        return new RoleAssignment($role, $apprenticeshipCode);
    }

    /** @return list<array{id: string, mail: string|null}>|null */
    public function getITApprentices(): ?array
    {
        return $this->membersOf('apprentices_IT');
    }

    /** @return list<array{id: string, mail: string|null}>|null */
    public function getECApprentices(): ?array
    {
        return $this->membersOf('apprentices_EC');
    }

    /** @return list<array{id: string, mail: string|null}>|null */
    public function getTrainers(): ?array
    {
        return $this->membersOf('trainer');
    }

    /** @return list<array{id: string, mail: string|null}>|null */
    public function getCoaches(): ?array
    {
        return $this->membersOf('coach');
    }

    /** @return list<array{id: string, mail: string|null}>|null */
    private function membersOf(string $key): ?array
    {
        $groupId = array_search($key, config('services.azure.group_roles'), true);

        if ($groupId === false) {
            return null;
        }

        $members = $this->graph->getGroupMembers((string) $groupId);

        if ($members === null) {
            return null;
        }

        $mapped = [];

        foreach ($members as $member) {
            if (isset($member['id'])) {
                $mapped[] = ['id' => $member['id'], 'mail' => $member['mail'] ?? null];
            }
        }

        return $mapped;
    }
}
