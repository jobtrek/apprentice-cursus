<?php

namespace App\Support\Gradebook;

use App\Http\Resources\GradeResource;
use App\Models\User;

/**
 * Loads what a gradebook page shows: the apprentice's grades, their grade tree and
 * the averages Averages computes from both. The only part that queries the database.
 *
 * @phpstan-import-type Tree from Averages
 * @phpstan-import-type Result from Averages
 *
 * @phpstan-type Props array{grades: array<int, array<string, mixed>>, tree: Tree|null, averages: Result|null}
 */
final class Gradebook
{
    /** The same props for a user with no gradebook (e.g. a supervisor's home page). */
    public const EMPTY = ['grades' => [], 'tree' => null, 'averages' => null];

    /** @return Props */
    public static function for(User $user): array
    {
        $user->loadMissing('formationPlan');

        $grades = GradeResource::collection(
            $user->grades()
                ->withSemester()
                ->with(GradeResource::RELATIONS)
                // Lets the gradebook filter the commented grades.
                ->withCount('comments')
                ->orderBy('test_date')
                ->orderBy('id')
                ->get(),
        )->resolve();
        $tree = GradebookTree::for($user);

        return [
            'grades' => $grades,
            'tree' => $tree,
            'averages' => Averages::of($tree, $grades, $user->formationPlan?->shortName()),
        ];
    }
}
