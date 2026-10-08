<?php

namespace Database\Seeders;

use App\Enums\UserRole;
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
     * - trainer@example.com (IT trainer, Bastien Nicoud, trainer of apprentice-it)
     * - trainer-ec@example.com (EC trainer, trainer of apprentice-ec)
     * - apprentice-it@example.com (IT apprentice)
     * - apprentice-ec@example.com (EC apprentice)
     *
     * Trainers and apprentices are put in the standard (non-MP) context of
     * their section (created by EvaluationTreeSeeder). Without it, they are
     * seeded with no context.
     */
    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $this->seed('admin@example.com', 'Local Admin', UserRole::Admin, null);
        $coach = $this->seed('coach@example.com', 'Local Coach', UserRole::Coach, null);
        $trainer = $this->seed(
            'trainer@example.com',
            'Bastien Nicoud',
            UserRole::Trainer,
            $this->contextId(ApprenticeshipSeeder::IT),
        );
        $trainerEc = $this->seed(
            'trainer-ec@example.com',
            'Local Trainer EC',
            UserRole::Trainer,
            $this->contextId(ApprenticeshipSeeder::EC),
        );
        $this->seed(
            'apprentice-it@example.com',
            'Local Apprentice IT',
            UserRole::Apprentice,
            $this->contextId(ApprenticeshipSeeder::IT),
            $coach->id,
            $trainer->id,
        );
        $this->seed(
            'apprentice-ec@example.com',
            'Local Apprentice EC',
            UserRole::Apprentice,
            $this->contextId(ApprenticeshipSeeder::EC),
            $coach->id,
            $trainerEc->id,
        );
    }

    private function seed(string $email, string $name, UserRole $role, ?int $contextId, ?int $coachId = null, ?int $trainerId = null): User
    {
        // is_active, coach_id and trainer_id are not mass assignable.
        $user = User::query()->firstOrNew(['email' => $email]);
        $user->forceFill([
            'name' => $name,
            'password' => 'password',
            'is_active' => true,
            'apprenticeship_context_id' => $contextId,
            'coach_id' => $coachId,
            'trainer_id' => $trainerId,
        ])->save();

        $user->syncRoles($role->value);

        return $user;
    }

    /** Id of the standard (non-MP) context of a section, null when it has none yet. */
    private function contextId(string $apprenticeship): ?int
    {
        return ApprenticeshipContext::query()
            ->where('is_mp', false)
            ->whereRelation('apprenticeship', 'name', $apprenticeship)
            ->value('id');
    }
}
