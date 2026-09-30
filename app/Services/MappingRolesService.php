<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Services\Microsoft\AzureGraphService;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class MappingRolesService
{
    /**
     * Keys are the values of config('services.azure.group_roles'); apprenticeship is apprenticeships.code.
     *
     * @var array<string, array{role: UserRole, apprenticeship: string|null}>
     */
    private const GROUP_ROLES = [
        'apprentices_IT' => ['role' => UserRole::Apprentice, 'apprenticeship' => 'it'],
        'apprentices_EC' => ['role' => UserRole::Apprentice, 'apprenticeship' => 'ec'],
        'trainer' => ['role' => UserRole::Trainer, 'apprenticeship' => null],
    ];

    public function __construct(private readonly AzureGraphService $graph) {}

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
    public function getCollaborators(): ?array
    {
        return $this->membersOf('trainer');
    }

    /** @return list<array{id: string, mail: string|null}>|null */
    private function membersOf(string $role): ?array
    {
        $groupId = array_search($role, config('services.azure.group_roles'), true);

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

    /**
     * Resolve the app role of an Entra account from the mapped groups it belongs to.
     *
     * Returns null when the account is in no mapped group, or in more than one
     * (a conflict an administrator has to fix in Entra).
     *
     * @return array{role: UserRole, apprenticeship: string|null}|null
     *
     * @throws RuntimeException when Graph could not be queried
     */
    public function resolveRole(string $azureId): ?array
    {
        $groups = $this->graph->getGroups($azureId);

        if ($groups === null) {
            throw new RuntimeException('Microsoft Graph group lookup failed.');
        }

        $groupRoles = config('services.azure.group_roles');

        $matched = [];

        foreach ($groups as $group) {
            if (isset($group['id'], $groupRoles[$group['id']])) {
                $matched[] = $groupRoles[$group['id']];
            }
        }

        $matched = array_values(array_unique($matched));

        if (count($matched) !== 1) {
            if ($matched !== []) {
                Log::warning('Azure account is in more than one role group, not mapped.', [
                    'azure_id' => $azureId,
                    'groups' => $matched,
                ]);
            }

            return null;
        }

        return self::GROUP_ROLES[$matched[0]] ?? null;
    }
}
