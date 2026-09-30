<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            ApprenticeshipSeeder::class,
            RolesAndPermissionsSeeder::class,
            SkillSeeder::class,
            EvaluationTreeSeeder::class,
        ]);

        if (! app()->environment('local')) {
            return;
        }

        // Explicit ids 1..n, so they must be created before any other user.
        $this->call(DemoApprenticeSeeder::class);

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]); // the factory gives it the apprentice role

        $this->call(UserSeeder::class);

        User::query()
            ->whereBetween('id', [1, DemoApprenticeSeeder::COUNT])
            ->whereNull('coach_id')
            ->update(['coach_id' => User::query()->where('email', 'admin@example.com')->value('id')]);
    }
}
