<?php

namespace App\Services;

use App\Exceptions\SsoLoginException;
use App\Models\Apprenticeship;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Two\User as AzureUser;
use RuntimeException;

class MicrosoftLoginService
{
    public function __construct(private readonly MappingRolesService $mappingRoles) {}

    /**
     * @throws SsoLoginException
     */
    public function resolveUser(AzureUser $azureUser): User
    {
        $azureId = (string) $azureUser->getId();

        try {
            $mapping = $this->mappingRoles->resolveRole($azureId);
        } catch (RuntimeException $e) {
            throw SsoLoginException::groupLookupFailed($e);
        }

        if ($mapping === null) {
            throw SsoLoginException::noAccess($azureId);
        }

        return DB::transaction(function () use ($azureUser, $azureId, $mapping): User {
            $user = User::where('azure_id', $azureId)->first();

            if ($user === null && User::where('email', $azureUser->getEmail())->exists()) {
                throw SsoLoginException::emailConflict($azureId);
            }

            $isNew = $user === null;
            $user ??= $this->provision($azureUser);

            if (! $user->is_active) {
                throw SsoLoginException::deactivated($user);
            }

            $user->forceFill([
                'role' => $mapping['role'],
                'apprenticeship_id' => $this->resolveApprenticeship($user, $mapping['apprenticeship']),
            ])->save();

            if ($isNew) {
                Log::info('Microsoft SSO auto-provisioned a new account.', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'role' => $user->role->value,
                ]);
            }

            return $user;
        });
    }

    private function provision(AzureUser $azureUser): User
    {
        $user = new User([
            'name' => $azureUser->getName() ?: $azureUser->getNickname() ?: $azureUser->getEmail(),
            'email' => $azureUser->getEmail(),
            'azure_id' => $azureUser->getId(),
            'tenant_id' => config('services.azure.tenant'),
        ]);

        $user->is_active = true;

        return $user;
    }

    /**
     * @throws SsoLoginException
     */
    private function resolveApprenticeship(User $user, ?string $name): ?int
    {
        if ($name === null) {
            return null;
        }

        $apprenticeshipId = Apprenticeship::where('name', $name)->value('id');

        if ($apprenticeshipId === null) {
            throw SsoLoginException::apprenticeshipMissing($name);
        }

        if ($user->apprenticeship_id !== null && $user->apprenticeship_id !== $apprenticeshipId) {
            Log::warning('Microsoft SSO: section change pending confirmation, apprenticeship kept.', [
                'user_id' => $user->id,
                'from' => $user->apprenticeship_id,
                'to' => $apprenticeshipId,
            ]);

            return $user->apprenticeship_id;
        }

        return $apprenticeshipId;
    }
}
