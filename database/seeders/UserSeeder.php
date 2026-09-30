<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Apprenticeship;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Seed local email/password accounts for accessing the app without Azure SSO.
     *
     * LOCAL ONLY (password login is disabled elsewhere): never run outside the
     * local environment. All accounts use the password "password":
     * - admin@example.com (coach, coach of the local apprentices)
     * - trainer@example.com (IT trainer)
     * - apprentice-it@example.com (IT apprentice)
     * - apprentice-ec@example.com (EC apprentice)
     */
    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $coach = $this->seed('admin@example.com', 'Local Admin', UserRole::Coach, null);
        $this->seed(
            'trainer@example.com',
            'Local Trainer',
            UserRole::Trainer,
            Apprenticeship::where('name', ApprenticeshipSeeder::IT)->value('id'),
        );
        $this->seed(
            'apprentice-it@example.com',
            'Local Apprentice IT',
            UserRole::Apprentice,
            Apprenticeship::where('name', ApprenticeshipSeeder::IT)->value('id'),
            $coach->id,
        );
        $this->seed(
            'apprentice-ec@example.com',
            'Local Apprentice EC',
            UserRole::Apprentice,
            Apprenticeship::where('name', ApprenticeshipSeeder::EC)->value('id'),
            $coach->id,
        );
    }

    private function seed(string $email, string $name, UserRole $role, ?int $apprenticeshipId, ?int $coachId = null): User
    {
        // is_active, apprenticeship_id and coach_id are not mass assignable.
        $user = User::query()->firstOrNew(['email' => $email]);
        $user->forceFill([
            'name' => $name,
            'password' => 'password',
            'is_active' => true,
            'apprenticeship_id' => $apprenticeshipId,
            'coach_id' => $coachId,
        ])->save();

        $user->syncRoles($role->value);

        return $user;
    }
}
