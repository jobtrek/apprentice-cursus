<?php

namespace App\Console\Commands;

use App\Services\Microsoft\AzureGraphService;
use Illuminate\Console\Command;

class AzureGroupsTest extends Command
{
    protected $signature = 'azure:groups {azureId : Entra object ID of the user}';

    protected $description = 'Fetch a user\'s Entra groups via Graph and map them to app roles';

    public function handle(AzureGraphService $graph): int
    {
        $groups = $graph->getGroups($this->argument('azureId'));

        if ($groups === null) {
            $this->error('Graph call failed (check logs, token and GroupMember.Read.All consent).');

            return self::FAILURE;
        }

        $this->table(
            ['id', 'displayName'],
            array_map(fn (array $g) => [$g['id'] ?? '', $g['displayName'] ?? ''], $groups),
        );

        $map = config('services.azure.group_roles');
        $roles = array_values(array_intersect_key($map, array_flip(array_column($groups, 'id'))));
        if (! $roles) {
            echo 'roles dont exist';
        }

        $this->info('Roles: '.($roles === [] ? '(none)' : implode(', ', $roles)));

        return self::SUCCESS;
    }
}
