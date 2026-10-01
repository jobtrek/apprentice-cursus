<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /** Demo apprentice ids (IT) seeded with trainer@example.com as their trainer. */
    public const TRAINED_DEMO_APPRENTICES = [1, 2];

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

        // The IT trainer gets the first IT demo apprentices; the others stay
        // without a trainer so the trainer's "Ajouter" button has something to offer.
        User::query()
            ->whereIn('email', array_map(DemoApprenticeSeeder::email(...), self::TRAINED_DEMO_APPRENTICES))
            ->whereNull('trainer_id')
            ->update(['trainer_id' => User::query()->where('email', 'trainer@example.com')->value('id')]);

        $this->call(DemoGradeSeeder::class);
    }
}
