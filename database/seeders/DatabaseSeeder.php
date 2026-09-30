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
        $this->call(ApprenticeshipSeeder::class);

        // Explicit ids 1..n, so they must be created before any other user.
        $this->call(DemoApprenticeSeeder::class);

        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->call([
            SkillSeeder::class,
            EvaluationTreeSeeder::class,
            UserSeeder::class,
        ]);

        User::query()
            ->whereBetween('id', [1, DemoApprenticeSeeder::COUNT])
            ->whereNull('coach_id')
            ->update(['coach_id' => User::query()->where('email', 'admin@example.com')->value('id')]);

        // Runs last: WithoutModelEvents disables User's role-sync hook, so the seeded users get their Spatie roles here.
        $this->call(RolesAndPermissionsSeeder::class);
    }
}
