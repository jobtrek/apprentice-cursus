<?php

namespace App\Console\Commands;

use App\Services\Microsoft\AzureGraphService;
use Illuminate\Console\Command;

class AzureGroupMembersTest extends Command
{
    protected $signature = 'azure:members {groupId? : Entra group ID (defaults to every group in services.azure.group_roles)}';

    protected $description = 'List the members of an Entra group via Graph';

    public function handle(AzureGraphService $graph): int
    {
        $map = config('services.azure.group_roles');
        $groupId = $this->argument('groupId');
        $groupIds = $groupId ? [$groupId] : array_keys($map);

        foreach ($groupIds as $id) {
            $this->line("<info>{$id}</info> (".($map[$id] ?? 'unmapped').')');

            $members = $graph->getGroupMembers((string) $id);

            if ($members === null) {
                $this->error('Graph call failed (check logs, token and GroupMember.Read.All consent).');

                return self::FAILURE;
            }

            $this->table(
                ['id', 'displayName', 'mail'],
                array_map(fn (array $m) => [$m['id'] ?? '', $m['displayName'] ?? '', $m['mail'] ?? ''], $members),
            );
        }

        return self::SUCCESS;
    }
}
