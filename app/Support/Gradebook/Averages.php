<?php

namespace App\Support\Gradebook;

/**
 * An apprentice's averages, computed as for the CFC: grades sit on the leaves of the
 * grade tree, every parent node takes the weighted average of its graded
 * children (`average_nodes.weight`), then the node's `rounding_step` is applied.
 * Pure: takes the tree and the grades, queries nothing. Matches `Averages` in resources/js/lib/gradebook.ts.
 *
 * @phpstan-type Tree array{root: int, nodes: array<int, array{id: int, name: string, aggregated: bool, rounding_step: float|null, children: list<array{id: int, weight: float}>}>}
 * @phpstan-type GradeRow array{id: int, node_id: int, value: float, semester: int}
 * @phpstan-type Group array{id: int, name: string, weight: float, rounding_step: float|null, average: float|null, grade_ids: list<int>}
 * @phpstan-type Domain array{id: int, name: string, weight: float, rounding_step: float|null, average: float|null, grade_ids: list<int>, subs: list<Group>}
 * @phpstan-type Result array{final: float|null, complete: bool, current_semester: int, semester_count: int, semesters: list<array{semester: int, average: float|null, count: int}>, domains: list<Domain>}
 */
final class Averages
{
    /** An IT apprenticeship lasts 4 years, an EC one 3. */
    private const SEMESTERS_BY_SECTION = ['IT' => 8, 'EC' => 6];

    /** @var array<int, list<int>> */
    private array $leaves = [];

    /**
     * @param  Tree  $tree
     * @param  list<GradeRow>  $grades
     */
    private function __construct(private readonly array $tree, private readonly array $grades) {}

    /**
     * @param  Tree|null  $tree
     * @param  array<int, array<string, mixed>>  $grades  resolved GradeResource rows
     * @return Result|null
     */
    public static function of(?array $tree, array $grades, ?string $section): ?array
    {
        if ($tree === null) {
            return null;
        }

        /** @var list<GradeRow> $rows */
        $rows = array_values($grades);
        $book = new self($tree, $rows);
        $root = $tree['root'];

        $semesters = [];

        for ($semester = 1; $semester <= Semester::MAX; $semester++) {
            $in = array_values(array_filter($rows, fn (array $grade): bool => $grade['semester'] === $semester));
            $semesters[] = ['semester' => $semester, 'average' => $book->nodeValue($root, $in), 'count' => count($in)];
        }

        return [
            'final' => $book->nodeValue($root, $rows),
            'complete' => $book->isComplete($root),
            // Without an apprenticeship start date: the last graded semester (1 without grades).
            'current_semester' => min(Semester::MAX, max([1, ...array_column($rows, 'semester')])),
            'semester_count' => self::SEMESTERS_BY_SECTION[$section ?? ''] ?? Semester::MAX,
            'semesters' => $semesters,
            // Domains = children of the root (TPI, Culture générale…); sub-groups = their composite children.
            'domains' => array_map(fn (array $child): array => $book->group($child) + [
                'subs' => array_values(array_map(
                    $book->group(...),
                    array_filter(
                        $tree['nodes'][$child['id']]['children'] ?? [],
                        fn (array $sub): bool => $tree['nodes'][$sub['id']]['aggregated'] ?? false,
                    ),
                )),
            ], $tree['nodes'][$root]['children'] ?? []),
        ];
    }

    /**
     * @param  array{id: int, weight: float}  $child
     * @return Group
     */
    private function group(array $child): array
    {
        $leaves = array_flip($this->leavesOf($child['id']));
        $under = array_values(array_filter($this->grades, fn (array $grade): bool => isset($leaves[$grade['node_id']])));

        return [
            'id' => $child['id'],
            'name' => $this->tree['nodes'][$child['id']]['name'] ?? '',
            'weight' => $child['weight'],
            'rounding_step' => $this->tree['nodes'][$child['id']]['rounding_step'] ?? null,
            'average' => $this->nodeValue($child['id'], $under),
            'grade_ids' => array_column($under, 'id'),
        ];
    }

    /**
     * Weighted average of a node over $grades; null when nothing below it is graded.
     *
     * @param  list<GradeRow>  $grades
     */
    private function nodeValue(int $id, array $grades, bool $round = true): ?float
    {
        $node = $this->tree['nodes'][$id] ?? null;

        if ($node === null) {
            return null;
        }

        if (! $node['aggregated']) {
            $own = array_column(array_filter($grades, fn (array $grade): bool => $grade['node_id'] === $id), 'value');

            if ($own === []) {
                return null;
            }

            $value = array_sum($own) / count($own);
        } else {
            $sum = 0.0;
            $weights = 0.0;

            foreach ($node['children'] as $child) {
                $childValue = $this->nodeValue($child['id'], $grades, false);

                if ($childValue !== null) {
                    $sum += $childValue * $child['weight'];
                    $weights += $child['weight'];
                }
            }

            if ($weights == 0) {
                return null;
            }

            $value = $sum / $weights;
        }

        $step = $node['rounding_step'];

        return $round && $step ? round($value / $step) * $step : $value;
    }

    /**
     * Every evaluation counted under this node has a grade: each leaf is graded and
     * each child with a non-zero weight is complete. nodeValue() skips ungraded
     * children and spreads their weight on the others: until then the average is provisional.
     */
    private function isComplete(int $id): bool
    {
        $node = $this->tree['nodes'][$id] ?? null;

        if ($node === null) {
            return false;
        }

        if (! $node['aggregated']) {
            return in_array($id, array_column($this->grades, 'node_id'), true);
        }

        foreach ($node['children'] as $child) {
            if ($child['weight'] > 0 && ! $this->isComplete($child['id'])) {
                return false;
            }
        }

        return true;
    }

    /** @return list<int> */
    private function leavesOf(int $id): array
    {
        if (isset($this->leaves[$id])) {
            return $this->leaves[$id];
        }

        $node = $this->tree['nodes'][$id] ?? null;

        return $this->leaves[$id] = $node === null || ! $node['aggregated']
            ? [$id]
            : array_merge(...array_map(fn (array $child): array => $this->leavesOf($child['id']), $node['children']));
    }
}
