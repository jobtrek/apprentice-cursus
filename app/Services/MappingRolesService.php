<?php

namespace App\Services;

use App\Enums\AzureGroup;
use App\Services\Microsoft\MicrosoftGraphService;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class MappingRolesService
{
    public function __construct(private readonly MicrosoftGraphService $graph) {}

    /**
     * Resolve the single mapped Entra group of an account, or null when the
     * account is in no mapped group or in several of them.
     *
     * @throws RuntimeException when the Graph lookup fails
     */
    public function resolveGroup(string $azureId): ?AzureGroup
    {
        $groups = $this->graph->getGroups($azureId);

        if ($groups === null) {
            throw new RuntimeException('Microsoft Graph group lookup failed.');
        }

        $groupIds = array_values(array_filter(array_column($groups, 'id'), 'is_string'));
        $group = AzureGroup::resolveFrom($groupIds);

        if ($group === null) {
            self::warnIfAmbiguous($azureId, $groupIds);
        }

        return $group;
    }

    /**
     * @param  list<string>  $groupIds
     */
    public static function warnIfAmbiguous(string $azureId, array $groupIds): void
    {
        $matched = AzureGroup::matching($groupIds);

        if (count($matched) > 1) {
            Log::warning('Azure account is in more than one role group, not mapped.', [
                'azure_id' => $azureId,
                'groups' => array_map(fn (AzureGroup $g) => $g->value, $matched),
            ]);
        }
    }
}
