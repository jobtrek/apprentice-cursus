<?php

namespace App\Support\Gradebook;

use App\Enums\EvaluationVariant;
use App\Models\EvaluationNode;
use App\Models\EvaluationNodeConnection;
use App\Models\User;

/**
 * The apprentice's grade tree, as the gradebook pages need it to compute the
 * CFC averages: every node reachable from the apprenticeship's root, with its
 * rounding step and its weighted children. Nodes of the other variant
 * (standard vs MP) are left out. Matches `GradeTree` in resources/js/lib/gradebook.ts.
 */
final class GradebookTree
{
    /**
     * @return array{root: int, nodes: array<int, array{id: int, name: string, aggregated: bool, rounding_step: float|null, period_scope: string, children: list<array{id: int, weight: float}>}>}|null
     */
    public static function for(User $user): ?array
    {
        $rootId = $user->apprenticeship?->evaluation_node_id;

        if ($rootId === null) {
            return null;
        }

        $variant = $user->is_mp ? EvaluationVariant::Mp : EvaluationVariant::Standard;
        $nodes = [];
        $level = [$rootId];

        // A few queries per tree level (the trees are 3–4 levels deep).
        while ($level !== []) {
            $connections = EvaluationNodeConnection::query()
                ->whereIn('parent_id', $level)
                ->orderBy('id')
                ->get();

            $related = EvaluationNode::query()
                ->whereIn('id', [...$level, ...$connections->pluck('child_id')->all()])
                ->get()
                ->keyBy('id');

            // Children of the other variant (standard vs MP) are left out.
            $connections = $connections->filter(function (EvaluationNodeConnection $connection) use ($related, $variant): bool {
                $child = $related->get($connection->child_id);

                return $child !== null && ($child->variant === null || $child->variant === $variant);
            });

            $next = [];

            foreach ($level as $id) {
                $node = $related->get($id);

                if ($node === null) {
                    continue;
                }

                $children = [];

                foreach ($connections->where('parent_id', $id) as $connection) {
                    $children[] = ['id' => $connection->child_id, 'weight' => (float) $connection->weight];

                    if (! isset($nodes[$connection->child_id])) {
                        $next[$connection->child_id] = $connection->child_id;
                    }
                }

                $nodes[$id] = [
                    'id' => $node->id,
                    'name' => $node->name,
                    'aggregated' => ! $node->isLeaf(),
                    'rounding_step' => $node->rounding_step === null ? null : (float) $node->rounding_step,
                    'period_scope' => $node->period_scope->value,
                    'children' => $children,
                ];
            }

            $level = array_values($next);
        }

        return ['root' => $rootId, 'nodes' => $nodes];
    }
}
