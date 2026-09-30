<?php

namespace App\Services\Microsoft;

use App\Models\User;
use Illuminate\Support\Facades\Log;

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

    public function compareIdsFromAzure(int $id): ?string
    {
        $azureId = User::whereKey($id)->value('azure_id');

        if ($azureId === null) {
            return null;
        }

        $groupRoles = config('services.azure.group_roles');

        foreach ($groupRoles as $groupId => $role) {
            $memberIds = array_column($this->membersOf($role) ?? [], 'id');

            if (in_array($azureId, $memberIds, true)) {
                Log::info("User {$id} belongs to group {$role}", ['group_id' => $groupId]);

                return $role;
            }
        }

        return null;
    }
}
