<?php

namespace App\Services;

use App\Exceptions\FormationPlanNotSeededException;
use App\Exceptions\SsoLoginException;
use App\Models\User;
use Laravel\Socialite\Two\User as AzureUser;
use RuntimeException;

class MicrosoftLoginService
{
    public function __construct(
        private readonly MappingRolesService $mappingRoles,
        private readonly AzureAccountSync $sync,
    ) {}

    /**
     * @throws SsoLoginException
     */
    public function resolveUser(AzureUser $azureUser): User
    {
        $azureId = (string) $azureUser->getId();

        try {
            $group = $this->mappingRoles->resolveGroup($azureId);
        } catch (RuntimeException $e) {
            throw SsoLoginException::groupLookupFailed($e);
        }

        if ($group === null) {
            $existing = User::where('azure_id', $azureId)->first();

            if ($existing !== null) {
                $this->sync->deactivate($existing);
            }

            throw SsoLoginException::noAccess($azureId);
        }

        $user = User::where('azure_id', $azureId)->first();

        if ($user === null) {
            throw SsoLoginException::notSynced($azureId);
        }

        try {
            $this->sync->apply($user, $group);
        } catch (FormationPlanNotSeededException $e) {
            throw SsoLoginException::formationPlanMissing($e->formationPlan);
        }

        return $user;
    }
}
