<?php

namespace App\Support\Gradebook;

use App\Models\AverageNode;
use App\Models\Grade;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * The apprentice's grade tree with its CFC averages already computed: every node
 * of the apprentice's formation plan, with its rounding step, its weighted children,
 * its average, its grade count and whether every evaluation under it is graded.
 * Nodes of another plan (e.g. the other MP variant branch) are left out.
 * Matches `GradeTree` in resources/js/lib/gradebook.ts.
 *
 * Averages follow the CFC: grades sit on the leaves, a parent is the weighted
 * average of its graded children (`average_nodes.weight`), then the node's
 * `rounding_step` is applied.
 *
 * @phpstan-type Node array{id: int, name: string, aggregated: bool, rounding_step: float|null, children: list<array{id: int, weight: float}>}
 */
final class GradebookTree
{
    /** IT lasts 4 years, EC 3. */
    private const SEMESTERS_BY_TRACK = ['IT' => 8, 'EC' => 6];

    /**
     * @param  Collection<int, Grade>  $grades  the apprentice's grades, loaded with `withSemester()`; empty when only the form needs the tree
     * @return array{root: int, nodes: array<int, array{id: int, name: string, aggregated: bool, rounding_step: float|null, children: list<array{id: int, weight: float}>, value: float|null, count: int, complete: bool, leaves: list<int>}>, groups: list<array{name: string, branches: list<array{id: int, name: string}>}>, semesters: array<int, float|null>, semester_count: int, current_semester: int, previous_semester: int|null, missing_domains: list<int>, failed_count: int}|null
     */
    public static function for(User $user, Collection $grades = new Collection): ?array
    {
        $plan = $user->formationPlan;

        if ($plan === null) {
            return null;
        }

        // One query: the plan lists all of its nodes, the tree is rebuilt from parent_id.
        $planNodes = $plan->averageNodes()->orderBy('average_nodes.id')->get();
        $root = $planNodes->firstWhere('parent_id', null);

        if (! $root instanceof AverageNode) {
            return null;
        }

        $childrenOf = $planNodes->whereNotNull('parent_id')->groupBy('parent_id');
        /** @var array<int, Node> $nodes */
        $nodes = [];

        foreach ($planNodes as $node) {
            $children = array_values($childrenOf->get($node->id, collect())
                ->map(fn (AverageNode $child): array => ['id' => $child->id, 'weight' => (float) $child->weight])
                ->all());

            $nodes[$node->id] = [
                'id' => $node->id,
                'name' => $node->name,
                'aggregated' => $children !== [],
                'rounding_step' => $node->rounding_step === null ? null : (float) $node->rounding_step,
                'children' => $children,
            ];
        }

        $byLeaf = self::valuesByLeaf($grades);
        $withStats = [];

        foreach ($nodes as $id => $node) {
            $withStats[$id] = $node + [
                'value' => self::value($nodes, $id, $byLeaf),
                'count' => self::count($nodes, $id, $byLeaf),
                'complete' => self::isComplete($nodes, $id, $byLeaf),
                'leaves' => self::leaves($nodes, $id),
            ];
        }

        $semesters = [];

        for ($semester = 1; $semester <= Semester::MAX; $semester++) {
            $inSemester = $grades->filter(fn (Grade $grade): bool => $grade->semester === $semester);
            $semesters[$semester] = self::value($nodes, $root->id, self::valuesByLeaf($inSemester));
        }

        // No apprenticeship start date in the database: the last graded semester is the current one.
        $graded = $grades->map(fn (Grade $grade): int => $grade->semester)->unique()->sortDesc()->values();
        $lastGraded = (int) $graded->first();

        return [
            'root' => $root->id,
            'nodes' => $withStats,
            'groups' => self::groups($nodes, $root->id),
            'semesters' => $semesters,
            'semester_count' => self::SEMESTERS_BY_TRACK[$plan->shortName()] ?? Semester::MAX,
            'current_semester' => min(Semester::MAX, max(1, $lastGraded)),
            // The graded semester before the current one, for the semester-over-semester delta.
            'previous_semester' => $graded->get(1),
            // Domains without any grade yet: the final average is provisional without them.
            'missing_domains' => array_values(array_map(
                fn (array $child): int => $child['id'],
                array_filter($nodes[$root->id]['children'], fn (array $child): bool => $withStats[$child['id']]['value'] === null),
            )),
            'failed_count' => $grades->filter(fn (Grade $grade): bool => (float) $grade->value < Grading::PASS)->count(),
        ];
    }

    /**
     * @param  Collection<int, Grade>  $grades
     * @return array<int, list<float>>
     */
    private static function valuesByLeaf(Collection $grades): array
    {
        $byLeaf = [];

        foreach ($grades as $grade) {
            $byLeaf[$grade->average_node_id][] = (float) $grade->value;
        }

        return $byLeaf;
    }

    /**
     * Weighted average of a node; null when nothing under it is graded.
     *
     * @param  array<int, Node>  $nodes
     * @param  array<int, list<float>>  $byLeaf
     */
    private static function value(array $nodes, int $id, array $byLeaf, bool $round = true): ?float
    {
        $node = $nodes[$id] ?? null;

        if ($node === null) {
            return null;
        }

        if (! $node['aggregated']) {
            $own = $byLeaf[$id] ?? [];

            if ($own === []) {
                return null;
            }

            $value = array_sum($own) / count($own);
        } else {
            $sum = 0.0;
            $weights = 0.0;

            // Ungraded children are skipped: their weight goes to the graded ones.
            foreach ($node['children'] as $child) {
                $childValue = self::value($nodes, $child['id'], $byLeaf, false);

                if ($childValue !== null) {
                    $sum += $childValue * $child['weight'];
                    $weights += $child['weight'];
                }
            }

            if ($weights === 0.0) {
                return null;
            }

            $value = $sum / $weights;
        }

        $step = $node['rounding_step'];

        // Rounded twice: the step snaps the value, the 2 decimals drop float noise (4.3000000000000003).
        return $round && $step ? round(round($value / $step) * $step, 2) : $value;
    }

    /**
     * Leaves under a node (the node itself for a leaf): the front lists their grades under it.
     *
     * @param  array<int, Node>  $nodes
     * @return list<int>
     */
    private static function leaves(array $nodes, int $id): array
    {
        $node = $nodes[$id] ?? null;

        if ($node === null || ! $node['aggregated']) {
            return [$id];
        }

        return array_merge(...array_map(fn (array $child): array => self::leaves($nodes, $child['id']), $node['children']));
    }

    /**
     * Number of grades under a node.
     *
     * @param  array<int, Node>  $nodes
     * @param  array<int, list<float>>  $byLeaf
     */
    private static function count(array $nodes, int $id, array $byLeaf): int
    {
        $node = $nodes[$id] ?? null;

        if ($node === null || ! $node['aggregated']) {
            return count($byLeaf[$id] ?? []);
        }

        return array_sum(array_map(fn (array $child): int => self::count($nodes, $child['id'], $byLeaf), $node['children']));
    }

    /**
     * Every evaluation counted under this node has a grade: each leaf graded, and each
     * child of non-zero weight complete. Until then, the node's average is provisional.
     *
     * @param  array<int, Node>  $nodes
     * @param  array<int, list<float>>  $byLeaf
     */
    private static function isComplete(array $nodes, int $id, array $byLeaf): bool
    {
        $node = $nodes[$id] ?? null;

        if ($node === null) {
            return false;
        }

        if (! $node['aggregated']) {
            return isset($byLeaf[$id]);
        }

        foreach ($node['children'] as $child) {
            if ($child['weight'] > 0 && ! self::isComplete($nodes, $child['id'], $byLeaf)) {
                return false;
            }
        }

        return true;
    }

    /**
     * The leaves the grade form offers, grouped by direct parent (« Modules école
     * professionnelle », « Culture générale »…), in tree order.
     *
     * @param  array<int, Node>  $nodes
     * @return list<array{name: string, branches: list<array{id: int, name: string}>}>
     */
    private static function groups(array $nodes, int $id): array
    {
        $node = $nodes[$id] ?? null;

        if ($node === null) {
            return [];
        }

        $leaves = [];

        foreach ($node['children'] as $child) {
            $childNode = $nodes[$child['id']] ?? null;

            if ($childNode !== null && ! $childNode['aggregated']) {
                $leaves[] = ['id' => $childNode['id'], 'name' => $childNode['name']];
            }
        }

        $groups = $leaves === [] ? [] : [['name' => $node['name'], 'branches' => $leaves]];

        foreach ($node['children'] as $child) {
            array_push($groups, ...self::groups($nodes, $child['id']));
        }

        return $groups;
    }
}
