<?php

namespace App\Services;

use App\Enums\AzureGroup;
use App\Exceptions\ApprenticeshipNotSeededException;
use App\Exceptions\AzureAccessRevokedException;
use App\Models\Apprenticeship;
use App\Models\ApprenticeshipContext;
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
     * Apply the group's role (Spatie) and context to the user and (re)activate it.
     * A user whose role is unchanged keeps a different existing context.
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
        $currentContext = $user->apprenticeshipContext;
        $context = null;

        if ($roleUnchanged && $currentContext !== null && $currentContext->apprenticeship_id === $apprenticeshipId) {
            $context = $currentContext;
        } elseif ($roleUnchanged && $currentContext !== null) {
            Log::warning('Microsoft SSO: section change pending confirmation, apprenticeship kept.', [
                'user_id' => $user->id,
                'from' => $currentContext->apprenticeship_id,
                'to' => $apprenticeshipId,
            ]);

            $context = $currentContext;
        }

        if ($context === null) {
            $context = ApprenticeshipContext::query()
                ->where('apprenticeship_id', $apprenticeshipId)
                ->where('is_mp', false)
                ->first();
        }

        if ($context === null) {
            throw new ApprenticeshipNotSeededException($name);
        }

        $user->forceFill([
            'apprenticeship_context_id' => $context->id,
            'is_active' => true,
        ]);

        if (! $user->exists || $user->isDirty()) {
            $user->save();
        }

        if (! $roleUnchanged) {
            $user->syncRoles($group->role()->value);
        }
    }

    public function deactivate(User $user): void
    {
        $user->forceFill(['is_active' => false])->save();
    }
}
