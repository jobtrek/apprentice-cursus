<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Roles must exist before any user is saved: User syncs its Spatie role on save.
        $this->call(RolesAndPermissionsSeeder::class);

        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->call([
            ApprenticeshipSeeder::class,
            SkillSeeder::class,
            EvaluationTreeSeeder::class,
            UserSeeder::class,
        ]);
    }
}
