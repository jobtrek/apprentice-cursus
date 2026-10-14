<?php

namespace App\Services;

use App\Enums\AzureGroup;
use App\Enums\UserRole;
use App\Exceptions\AzureAccessRevokedException;
use App\Exceptions\FormationPlanNotSeededException;
use App\Models\FormationPlan;
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
     * Apply the group's role (Spatie) and formation plans to the user and (re)activate it.
     * An apprentice starts on the section's first plan and keeps another plan of the same
     * section (the MP variant is chosen by hand). It also keeps a plan of another section:
     * moving grades needs a confirmation. A trainer trains every plan of its section;
     * any other role follows none.
     *
     * @throws FormationPlanNotSeededException before anything is modified
     */
    public function apply(User $user, AzureGroup $group): void
    {
        /** @var array<string, int> $planIds */
        $planIds = FormationPlan::query()
            ->whereIn('name', $group->formationPlans())
            ->pluck('id', 'name')
            ->map(fn (int|string $id): int => (int) $id)
            ->all();

        foreach ($group->formationPlans() as $name) {
            if (! isset($planIds[$name])) {
                throw new FormationPlanNotSeededException($name);
            }
        }

        $isApprentice = $group->role() === UserRole::Apprentice;
        $roleUnchanged = $user->role === $group->role();
        $planId = $isApprentice ? ($planIds[$group->formationPlans()[0]] ?? null) : null;

        if ($isApprentice && $roleUnchanged && $user->formation_plan_id !== null && $user->formation_plan_id !== $planId) {
            if (! in_array($user->formation_plan_id, $planIds, true)) {
                Log::warning('Microsoft SSO: section change pending confirmation, formation plan kept.', [
                    'user_id' => $user->id,
                    'from' => $user->formation_plan_id,
                    'to' => $planId,
                ]);
            }

            $planId = $user->formation_plan_id;
        }

        $user->forceFill([
            'formation_plan_id' => $planId,
            'is_active' => true,
        ]);

        if (! $user->exists || $user->isDirty()) {
            $user->save();
        }

        $user->trainedFormationPlans()->sync(
            $group->role() === UserRole::Trainer ? array_values($planIds) : [],
        );

        if (! $roleUnchanged) {
            $user->syncRoles($group->role()->value);
        }
    }

    public function deactivate(User $user): void
    {
        $user->forceFill(['is_active' => false])->save();
    }
}
