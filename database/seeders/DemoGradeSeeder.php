<?php

namespace Database\Seeders;

use App\Enums\PeriodScope;
use App\Models\EvaluationNode;
use App\Models\EvaluationNodeConnection;
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
 * Each grade is put on a semester-scoped leaf of the apprentice's own tree, found
 * by walking down from the apprenticeship's root node. Nodes are never looked up
 * by name (see EvaluationTreeSeeder). A user who already has a grade is left
 * untouched, so reseeding never duplicates or overwrites anything.
 */
class DemoGradeSeeder extends Seeder
{
    /**
     * @var list<array{value: string, semester: int, test_date: string}>
     */
    public const array GRADES = [
        ['value' => '6.0', 'semester' => 1, 'test_date' => '2026-03-12'],
        ['value' => '5.5', 'semester' => 1, 'test_date' => '2026-04-02'],
        ['value' => '4.5', 'semester' => 2, 'test_date' => '2026-05-14'],
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

        /** @var array<int, list<int>> $leavesByApprenticeship */
        $leavesByApprenticeship = [];

        DB::transaction(function () use (&$leavesByApprenticeship): void {
            $users = User::query()
                ->whereIn('email', self::emails())
                ->with('apprenticeship')
                ->orderBy('id')
                ->get();

            foreach ($users as $user) {
                $apprenticeship = $user->apprenticeship;

                if ($apprenticeship === null || $apprenticeship->evaluation_node_id === null) {
                    continue;
                }

                if ($user->grades()->exists()) {
                    continue;
                }

                $leaves = $leavesByApprenticeship[$apprenticeship->id]
                    ??= $this->semesterLeaves($apprenticeship->evaluation_node_id);

                if ($leaves === []) {
                    continue;
                }

                foreach (self::GRADES as $index => $row) {
                    Grade::query()->create([
                        'user_id' => $user->id,
                        'evaluation_node_id' => $leaves[$index % count($leaves)],
                        'value' => $row['value'],
                        'semester' => $row['semester'],
                        'test_date' => $row['test_date'],
                    ]);
                }
            }
        });
    }

    /**
     * Ids of the semester-scoped leaves reachable from the root, ordered by id.
     *
     * Breadth-first over evaluation_node_connections with a visited set, like
     * EvaluationNode::hasDescendant(), so a shared child is only visited once.
     *
     * @return list<int>
     */
    private function semesterLeaves(int $rootId): array
    {
        $visited = [];
        $frontier = [$rootId];

        while ($frontier !== []) {
            $visited = array_merge($visited, $frontier);

            /** @var list<int> $frontier */
            $frontier = EvaluationNodeConnection::query()
                ->whereIn('parent_id', $frontier)
                ->pluck('child_id')
                ->map(fn (int|string $id): int => (int) $id)
                ->reject(fn (int $id): bool => in_array($id, $visited, true))
                ->unique()
                ->values()
                ->all();
        }

        $ids = EvaluationNode::query()
            ->whereKey($visited)
            ->whereNull('aggregation')
            ->where('period_scope', PeriodScope::Semester)
            ->orderBy('id')
            ->pluck('id')
            ->map(fn (int|string $id): int => (int) $id)
            ->all();

        return array_values($ids);
    }
}
