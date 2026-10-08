<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Demo apprentice ids given a trainer, by trainer email. The others keep no
     * trainer, so the trainers' "Ajouter un apprenti" dialog has someone to offer.
     */
    public const DEMO_TRAINEES = [
        'trainer@example.com' => [1, 2],
        'trainer-ec@example.com' => [3],
    ];

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

        $this->call(UserSeeder::class);

        // Only accounts the demo seeder created, not pre-existing users at those ids.
        User::query()
            ->whereIn('email', array_map(DemoApprenticeSeeder::email(...), range(1, DemoApprenticeSeeder::COUNT)))
            ->whereNull('coach_id')
            ->update(['coach_id' => User::query()->where('email', 'coach@example.com')->value('id')]);

        foreach (self::DEMO_TRAINEES as $trainerEmail => $ids) {
            User::query()
                ->whereIn('email', array_map(DemoApprenticeSeeder::email(...), $ids))
                ->whereNull('trainer_id')
                ->update(['trainer_id' => User::query()->where('email', $trainerEmail)->value('id')]);
        }
    }
}
