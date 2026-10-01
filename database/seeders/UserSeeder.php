<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Apprenticeship;
use App\Models\ApprenticeshipContext;
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
     * - trainer@example.com (IT trainer)
     * - apprentice-it@example.com (IT apprentice)
     * - apprentice-ec@example.com (EC apprentice)
     */
    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $this->seed('admin@example.com', 'Local Admin', UserRole::Admin, null);
        $coach = $this->seed('coach@example.com', 'Local Coach', UserRole::Coach, null);
        $this->seed(
            'trainer@example.com',
            'Local Trainer',
            UserRole::Trainer,
            $this->contextId(ApprenticeshipSeeder::IT),
        );
        $this->seed(
            'apprentice-it@example.com',
            'Local Apprentice IT',
            UserRole::Apprentice,
            $this->contextId(ApprenticeshipSeeder::IT),
            $coach->id,
        );
        $this->seed(
            'apprentice-ec@example.com',
            'Local Apprentice EC',
            UserRole::Apprentice,
            $this->contextId(ApprenticeshipSeeder::EC),
            $coach->id,
        );
    }

    private function contextId(string $apprenticeshipName): ?int
    {
        $apprenticeshipId = Apprenticeship::where('name', $apprenticeshipName)->value('id');

        return $apprenticeshipId === null
            ? null
            : ApprenticeshipContext::where('apprenticeship_id', $apprenticeshipId)->value('id');
    }

    private function seed(string $email, string $name, UserRole $role, ?int $contextId, ?int $coachId = null): User
    {
        // is_active and coach_id are not mass assignable.
        $user = User::query()->firstOrNew(['email' => $email]);
        $user->forceFill([
            'name' => $name,
            'password' => 'password',
            'is_active' => true,
            'apprenticeship_context_id' => $contextId,
            'coach_id' => $coachId,
        ])->save();

        $user->syncRoles($role->value);

        return $user;
    }
}
