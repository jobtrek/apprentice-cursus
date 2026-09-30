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
     * local environment. Credentials: admin@example.com / password (coach) and
     * trainer@example.com / password (IT trainer).
     */
    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $this->seed('admin@example.com', 'Local Admin', UserRole::Coach, null);
        $this->seed(
            'trainer@example.com',
            'Local Trainer',
            UserRole::Trainer,
            Apprenticeship::where('name', ApprenticeshipSeeder::IT)->value('id'),
        );
    }

    private function seed(string $email, string $name, UserRole $role, ?int $apprenticeshipId): void
    {
        // is_active and apprenticeship_id are not mass assignable.
        $user = User::query()->firstOrNew(['email' => $email]);
        $user->forceFill([
            'name' => $name,
            'password' => 'password',
            'is_active' => true,
            'apprenticeship_id' => $apprenticeshipId,
        ])->save();

        $user->syncRoles($role->value);
    }
}
