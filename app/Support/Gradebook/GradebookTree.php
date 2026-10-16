<?php

namespace App\Support\Gradebook;

use App\Models\AverageNode;
use App\Models\User;

/**
 * The apprentice's grade tree, as Gradebook needs it to compute the CFC averages
 * and the grade form to offer its leaves: every node of the apprentice's formation
 * plan, with its rounding step and its weighted children. Nodes of another plan
 * (e.g. the other MP variant branch) are left out. Matches `GradeTree` in resources/js/lib/gradebook.ts.
 */
final class GradebookTree
{
    /**
     * @return array{root: int, nodes: array<int, array{id: int, name: string, aggregated: bool, rounding_step: float|null, children: list<array{id: int, weight: float}>}>}|null
     */
    public static function for(User $user): ?array
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

        return ['root' => $root->id, 'nodes' => $nodes];
    }
}
