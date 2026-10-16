<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\FormationPlan;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Seed local email/password accounts for accessing the app without Azure SSO.
     *
     * LOCAL ONLY (password login is disabled elsewhere): never run outside the
     * local environment. All accounts use the password "password":
     * - admin@example.com (local admin: bypasses every check in the local environment)
     * - coach@example.com (coach of the local apprentices)
     * - trainer@example.com (IT trainer, trains the IT plan)
     * - trainer-ec@example.com (EC trainer, trains the EC and EC MP plans)
     * - apprentice-it@example.com (IT apprentice)
     * - apprentice-ec@example.com (EC apprentice)
     */
    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $plans = FormationPlan::query()->pluck('id', 'name');

        $this->seed('admin@example.com', 'Local Admin', UserRole::Admin);
        $coach = $this->seed('coach@example.com', 'Local Coach', UserRole::Coach);
        $this->seed('trainer@example.com', 'Local Trainer IT', UserRole::Trainer, trainedPlanIds: [
            (int) $plans[FormationPlan::IT],
        ]);
        $this->seed('trainer-ec@example.com', 'Local Trainer EC', UserRole::Trainer, trainedPlanIds: [
            (int) $plans[FormationPlan::EC],
            (int) $plans[FormationPlan::EC_MP],
        ]);
        $this->seed(
            'apprentice-it@example.com',
            'Local Apprentice IT',
            UserRole::Apprentice,
            (int) $plans[FormationPlan::IT],
            $coach->id,
        );
        $this->seed(
            'apprentice-ec@example.com',
            'Local Apprentice EC',
            UserRole::Apprentice,
            (int) $plans[FormationPlan::EC],
            $coach->id,
        );
    }

    /**
     * @param  list<int>  $trainedPlanIds
     */
    private function seed(string $email, string $name, UserRole $role, ?int $formationPlanId = null, ?int $coachId = null, array $trainedPlanIds = []): User
    {
        // is_active, formation_plan_id and coach_id are not mass assignable.
        $user = User::query()->firstOrNew(['email' => $email]);
        $user->forceFill([
            'name' => $name,
            'password' => 'password',
            'is_active' => true,
            'formation_plan_id' => $formationPlanId,
            'coach_id' => $coachId,
        ])->save();

        $user->syncRoles($role->value);
        $user->trainedFormationPlans()->sync($trainedPlanIds);

        return $user;
    }
}
