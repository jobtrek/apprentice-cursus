<?php

namespace App\Services;

use App\Enums\AzureGroup;
use App\Enums\UserRole;
use App\Exceptions\ApprenticeshipNotSeededException;
use App\Exceptions\AzureAccessRevokedException;
use App\Models\Apprenticeship;
use App\Models\User;
use App\Services\Microsoft\MicrosoftGraphService;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Single source of truth for keeping a local user in line with Entra ID,
 * shared by the SSO login, the periodic re-check middleware and the daily
 * account sync.
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
     * Apply the group's role (Spatie) and section to the user and (re)activate it.
     * A group without a section (coach) clears the context.
     * An apprentice keeps its context when it is already in the group's section
     * (so an MP context is not reset) and also when it is in another section
     * (moving grades needs a confirmation); otherwise it gets the standard
     * context of the group's section. A trainer always follows its group's
     * section, in the standard context. A section without a context yet leaves
     * the user without one.
     *
     * @throws ApprenticeshipNotSeededException before anything is modified
     */
    public function apply(User $user, AzureGroup $group): void
    {
        $name = $group->apprenticeship();
        $contextId = null;
        $roleUnchanged = $user->role === $group->role();

        if ($name !== null) {
            $apprenticeship = Apprenticeship::where('name', $name)->first()
                ?? throw new ApprenticeshipNotSeededException($name);

            $currentId = $user->apprenticeshipId();
            $isApprentice = $group->role() === UserRole::Apprentice;

            if ($isApprentice && $roleUnchanged && $currentId !== null && $currentId !== $apprenticeship->id) {
                Log::warning('Microsoft SSO: section change pending confirmation, apprenticeship kept.', [
                    'user_id' => $user->id,
                    'from' => $currentId,
                    'to' => $apprenticeship->id,
                ]);

                $contextId = $user->apprenticeship_context_id;
            } elseif ($isApprentice && $currentId === $apprenticeship->id) {
                $contextId = $user->apprenticeship_context_id;
            } else {
                $contextId = $apprenticeship->contexts()->where('is_mp', false)->value('id');
            }
        }

        $user->forceFill([
            'apprenticeship_context_id' => $contextId,
            'is_active' => true,
        ]);

        if ($user->isDirty('apprenticeship_context_id')) {
            $user->unsetRelation('apprenticeshipContext')->unsetRelation('apprenticeship');
        }

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
