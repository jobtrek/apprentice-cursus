<?php

namespace App\Services\Microsoft;

class MappingRolesService
{
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
}
