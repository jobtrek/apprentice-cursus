<?php

namespace App\Support;

use App\Http\Resources\ApprenticeResource;
use App\Models\Grade;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;

/**
 * Rows of the apprentice list, shared by the apprentices page and the
 * supervisor home: the apprentices the user follows, as `ApprenticeResource`
 * plus their grade statistics.
 */
class ApprenticeList
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function for(User $user): array
    {
        $apprentices = $user->listedApprentices()
            ->with(ApprenticeResource::RELATIONS)
            ->orderBy('name')
            ->get();

        $stats = self::gradeStats($apprentices->modelKeys());

        return collect(ApprenticeResource::collection($apprentices)->resolve())
            ->map(fn (array $row): array => [
                ...$row,
                'year' => $stats[$row['id']]['year'],
                'stats' => Arr::except($stats[$row['id']], 'year'),
            ])
            ->values()
            ->all();
    }

    /**
     * Grade statistics of one apprentice, the same as their row in the list.
     *
     * @return array{grades_count: int, average: float|null, last_grade_date: string|null}
     */
    public static function statsFor(User $apprentice): array
    {
        $stats = self::gradeStats([$apprentice->id])[$apprentice->id];

        return [
            'grades_count' => $stats['grades_count'],
            'average' => $stats['average'],
            'last_grade_date' => $stats['last_grade_date'],
        ];
    }

    /**
     * Apprentices the user can take on, for the "Ajouter un apprenti" dialog.
     *
     * @return array<int, array{id: int, name: string, track: string|null}>
     */
    public static function assignable(User $user): array
    {
        return $user->assignableApprentices()
            ->with('apprenticeship')
            ->orderBy('name')
            ->get()
            ->map(fn (User $apprentice): array => [
                'id' => $apprentice->id,
                'name' => $apprentice->name,
                'track' => $apprentice->apprenticeship?->shortName(),
            ])
            ->values()
            ->all();
    }

    /**
     * Latest grades of the followed apprentices, newest test first, for the
     * supervisor home. `comments_count` lets it flag grades nobody answered yet.
     *
     * @return array<int, array{id: int, apprentice_id: int, apprentice: string, title: string, value: float, date: string, comments_count: int}>
     */
    public static function latestGrades(User $user, int $limit = 8): array
    {
        return Grade::query()
            ->whereIn('user_id', $user->listedApprentices()->select('users.id'))
            ->with(['user:id,name', 'evaluationNode:id,name'])
            ->withCount('comments')
            ->orderByDesc('test_date')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (Grade $grade): array => [
                'id' => $grade->id,
                'apprentice_id' => $grade->user_id,
                'apprentice' => $grade->user->name,
                'title' => $grade->evaluationNode->name,
                // The decimal cast yields a string; the front formats a number.
                'value' => (float) $grade->value,
                'date' => $grade->test_date->format('d.m.Y'),
                'comments_count' => (int) $grade->comments_count,
            ])
            ->all();
    }

    /**
     * Grades per month over the last `$months` months (current one included),
     * by test date, and how many followed apprentices have one: oldest first,
     * months without grades kept at zero so the chart has no gaps.
     *
     * @return list<array{month: string, grades: int, apprentices: int}>
     */
    public static function monthlyActivity(User $user, int $months = 12): array
    {
        $start = CarbonImmutable::today()->startOfMonth()->subMonths($months - 1);

        $counts = Grade::query()
            ->toBase()
            ->whereIn('user_id', $user->listedApprentices()->select('users.id'))
            ->where('test_date', '>=', $start)
            ->groupByRaw("to_char(test_date, 'YYYY-MM')")
            ->selectRaw("to_char(test_date, 'YYYY-MM') as month, count(*) as grades, count(distinct user_id) as apprentices")
            ->get()
            ->keyBy('month');

        $activity = [];

        for ($i = 0; $i < $months; $i++) {
            $month = $start->addMonths($i)->format('Y-m');
            $row = $counts->get($month);

            $activity[] = [
                'month' => $month,
                'grades' => (int) ($row->grades ?? 0),
                'apprentices' => (int) ($row->apprentices ?? 0),
            ];
        }

        return $activity;
    }

    /**
     * One grouped query for every apprentice, instead of one per row.
     *
     * @param  array<int, int>  $apprenticeIds
     * @return array<int, array{grades_count: int, average: float|null, last_grade_date: string|null, year: int|null}>
     */
    private static function gradeStats(array $apprenticeIds): array
    {
        $aggregates = Grade::query()
            ->toBase()
            ->whereIn('user_id', $apprenticeIds)
            ->groupBy('user_id')
            ->selectRaw('user_id, count(*) as grades_count, avg(value) as average, max(test_date) as last_grade_date, max(semester) as last_semester')
            ->get()
            ->keyBy('user_id');

        $stats = [];

        foreach ($apprenticeIds as $id) {
            $row = $aggregates->get($id);

            $stats[$id] = [
                'grades_count' => (int) ($row->grades_count ?? 0),
                'average' => isset($row->average) ? round((float) $row->average, 1) : null,
                'last_grade_date' => isset($row->last_grade_date)
                    ? CarbonImmutable::parse($row->last_grade_date)->format('d.m.Y')
                    : null,
                // No start date is stored yet: the year of the latest semester graded
                // (S1-S2 → 1, …, S7-S8 → 4) stands in for the year of apprenticeship.
                'year' => isset($row->last_semester) ? (int) ceil($row->last_semester / 2) : null,
            ];
        }

        return $stats;
    }
}
