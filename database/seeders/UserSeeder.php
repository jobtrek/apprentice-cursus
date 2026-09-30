<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Seed a local email/password account for accessing the app without Azure SSO.
     *
     * Local dev/test credentials only: admin@example.com / password
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Local Admin',
                'password' => 'password',
                'role' => UserRole::SuperAdmin,
                'is_active' => true,
            ],
        );
    }
}
