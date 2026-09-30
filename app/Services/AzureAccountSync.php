<?php

namespace App\Services;

use App\Enums\AzureGroup;
use App\Exceptions\ApprenticeshipNotSeededException;
use App\Exceptions\AzureAccessRevokedException;
use App\Models\Apprenticeship;
use App\Models\User;
use App\Services\Microsoft\MicrosoftGraphService;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Single source of truth for keeping a local user in line with Entra ID,
 * shared by the SSO login and the periodic re-check middleware.
 */
class AzureAccountSync
{
    /** Seconds to wait before retrying Graph after a failed re-check. */
    public const BACKOFF_SECONDS = 60;

    public function __construct(
        private readonly MicrosoftGraphService $graph,
        private readonly MappingRolesService $mappingRoles,
    ) {}

    public static function checkCacheKey(User|int $user): string
    {
        $id = $user instanceof User ? $user->id : $user;

        return "azure-account-check:{$id}";
    }

    /**
     * Re-validate the account against Entra ID and return its mapped group.
     *
     * @throws AzureAccessRevokedException when the account is disabled or has no single mapped group
     * @throws RuntimeException when Graph could not be reached (callers should fail open)
     */
    public function check(User $user): AzureGroup
    {
        $azureId = (string) $user->azure_id;

        $enabled = $this->graph->isAccountEnabled($azureId);

        if ($enabled === null) {
            throw new RuntimeException('Microsoft Graph account lookup failed.');
        }

        if ($enabled === false) {
            throw AzureAccessRevokedException::disabled();
        }

        return $this->mappingRoles->resolveGroup($azureId)
            ?? throw AzureAccessRevokedException::noAccess();
    }

    /**
     * Apply the group's role (Spatie) and apprenticeship to the user and (re)activate it.
     * A user whose role is unchanged keeps a different existing apprenticeship.
     *
     * @throws ApprenticeshipNotSeededException before anything is modified
     */
    public function apply(User $user, AzureGroup $group): void
    {
        $name = $group->apprenticeship();
        $apprenticeshipId = Apprenticeship::where('name', $name)->value('id');

        if ($apprenticeshipId === null) {
            throw new ApprenticeshipNotSeededException($name);
        }

        $apprenticeshipId = (int) $apprenticeshipId;
        $roleUnchanged = $user->role === $group->role();

        if ($roleUnchanged && $user->apprenticeship_id !== null && $user->apprenticeship_id !== $apprenticeshipId) {
            Log::warning('Microsoft SSO: section change pending confirmation, apprenticeship kept.', [
                'user_id' => $user->id,
                'from' => $user->apprenticeship_id,
                'to' => $apprenticeshipId,
            ]);

            $apprenticeshipId = $user->apprenticeship_id;
        }

        $user->forceFill([
            'apprenticeship_id' => $apprenticeshipId,
            'is_active' => true,
        ]);

        if (! $user->exists || $user->isDirty()) {
            $user->save();
        }

        if (! $roleUnchanged) {
            $user->syncRoles($group->role()->value);
        }
    }

    /**
     * Unsaved account for an Entra user, shared by the SSO login and the apprentice
     * directory sync so both provision identically. Role and apprenticeship come from `apply()`.
     */
    public function newAccount(string $azureId, string $name, string $email): User
    {
        return new User([
            'name' => $name,
            'email' => $email,
            'azure_id' => $azureId,
            'tenant_id' => config('services.azure.tenant'),
        ]);
    }

    public function deactivate(User $user): void
    {
        $user->forceFill(['is_active' => false])->save();
    }
}
