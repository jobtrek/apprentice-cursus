<?php

namespace Database\Seeders;

use App\Models\Grade;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Gives the local demo and local apprentice accounts a few real grades, so the
 * gradebook pages and their detail links resolve to records instead of 404ing.
 *
 * LOCAL ONLY: never run outside the local environment.
 *
 * Accounts are found by email, never by id: that keeps this scoped to the
 * accounts the demo and user seeders created (see DemoApprenticeSeeder::email()),
 * even when another user already occupies a demo id.
 *
 * Each grade is put on a semester leaf (half-point rounding) of the apprentice's own
 * formation plan. Nodes are never looked up by name (see AverageNodeSeeder). A user
 * who already has a grade is left untouched, so reseeding never duplicates or
 * overwrites anything.
 */
class DemoGradeSeeder extends Seeder
{
    /**
     * Two grades in S1 (August–January) and one in S2, see Semester.
     *
     * @var list<array{value: string, test_date: string}>
     */
    public const array GRADES = [
        ['value' => '6.0', 'test_date' => '2025-09-12'],
        ['value' => '5.5', 'test_date' => '2025-11-20'],
        ['value' => '4.5', 'test_date' => '2026-03-14'],
    ];

    /**
     * @return list<string>
     */
    public static function emails(): array
    {
        return [
            ...array_map(DemoApprenticeSeeder::email(...), range(1, DemoApprenticeSeeder::COUNT)),
            'apprentice-it@example.com',
            'apprentice-ec@example.com',
        ];
    }

    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        /** @var array<int, list<int>> $leavesByPlan */
        $leavesByPlan = [];

        DB::transaction(function () use (&$leavesByPlan): void {
            $users = User::query()
                ->whereIn('email', self::emails())
                ->with('formationPlan')
                ->orderBy('id')
                ->get();

            foreach ($users as $user) {
                $plan = $user->formationPlan;

                if ($plan === null || $user->grades()->exists()) {
                    continue;
                }

                $leaves = $leavesByPlan[$plan->id] ??= array_values($plan->averageNodes()
                    ->where('rounding_step', 0.5)
                    ->whereDoesntHave('children')
                    ->orderBy('average_nodes.id')
                    ->pluck('average_nodes.id')
                    ->map(fn (int|string $id): int => (int) $id)
                    ->all());

                if ($leaves === []) {
                    continue;
                }

                foreach (self::GRADES as $index => $row) {
                    Grade::query()->create([
                        'user_id' => $user->id,
                        'average_node_id' => $leaves[$index % count($leaves)],
                        'value' => $row['value'],
                        'test_date' => $row['test_date'],
                    ]);
                }
            }
        });
    }
}
