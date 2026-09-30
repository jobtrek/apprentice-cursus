<?php

namespace App\Console\Commands;

use App\Services\Microsoft\MappingRolesService;
use Illuminate\Console\Command;

class AzureMappingRolesTest extends Command
{
    protected $signature = 'azure:roles {role? : it, ec or trainer (defaults to all)}';

    protected $description = 'List the members mapped to each role via MappingRolesService';

    public function handle(MappingRolesService $roles): int
    {
        $lookups = [
            'it' => ['apprentices_IT', fn () => $roles->getITApprentices()],
            'ec' => ['apprentices_EC', fn () => $roles->getECApprentices()],
            'trainer' => ['trainer', fn () => $roles->getCollaborators()],
        ];

        $selected = $this->argument('role');

        if ($selected !== null && ! isset($lookups[$selected])) {
            $this->error('Unknown role. Use: '.implode(', ', array_keys($lookups)));

            return self::FAILURE;
        }

        $failed = false;

        foreach ($selected ? [$selected] : array_keys($lookups) as $key) {
            [$label, $fetch] = $lookups[$key];

            $this->line("<info>{$label}</info>");

            $members = $fetch();

            if ($members === null) {
                $this->error('Lookup failed (check logs, group_roles config, token and GroupMember.Read.All consent).');
                $failed = true;

                continue;
            }

            $this->table(
                ['id', 'mail'],
                array_map(fn (array $m) => [$m['id'], $m['mail'] ?? ''], $members),
            );
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
