<?php

namespace App\Services;

use App\Exceptions\ApprenticeshipNotSeededException;
use App\Exceptions\SsoLoginException;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

        try {
            return DB::transaction(function () use ($azureUser, $azureId, $group): User {
                $user = User::where('azure_id', $azureId)->first();

                if ($user === null && User::where('email', $azureUser->getEmail())->exists()) {
                    throw SsoLoginException::emailConflict($azureId);
                }

                $isNew = $user === null;
                $user ??= $this->provision($azureUser);

                $this->sync->apply($user, $group);

                if ($isNew) {
                    Log::info('Microsoft SSO auto-provisioned a new account.', [
                        'user_id' => $user->id,
                        'email' => $user->email,
                        'role' => $user->role->value,
                    ]);
                }

                return $user;
            });
        } catch (ApprenticeshipNotSeededException $e) {
            throw SsoLoginException::apprenticeshipMissing($e->apprenticeship);
        }
    }

    private function provision(AzureUser $azureUser): User
    {
        return new User([
            'name' => $azureUser->getName() ?: $azureUser->getNickname() ?: $azureUser->getEmail(),
            'email' => $azureUser->getEmail(),
            'azure_id' => $azureUser->getId(),
            'tenant_id' => config('services.azure.tenant'),
        ]);
    }
}
