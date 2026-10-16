<?php

namespace App\Http\Resources;

use App\Models\Grade;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Matches the `Grade` type in resources/js/types/grade.ts.
 *
 * Expects `GradeResource::RELATIONS` to be eager loaded, and the query to use
 * Grade::withSemester() for lists (one query instead of one per grade).
 *
 * @mixin Grade
 */
class GradeResource extends JsonResource
{
    /**
     * Eager-load path for the graded node and its ancestors.
     *
     * In the seeded IT/EC trees a leaf is at most 3 levels below the root
     * (leaf -> subdomain/variant -> domain -> root). The 4th `parent` level
     * loads the root's (null) parent, so the ancestor walk knows it reached
     * the top without lazy loading. A deeper tree still resolves correctly,
     * only through lazy loads.
     */
    public const RELATIONS = 'averageNode.parent.parent.parent.parent';

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $parent = $this->averageNode->parent;

        return [
            'id' => $this->id,
            'node_id' => $this->average_node_id,
            'title' => $this->averageNode->name,
            // Direct parent; empty when the node has none.
            'subject' => $parent === null ? '' : $parent->name,
            // Ancestor names from the top-level domain (child of the root) down to the direct parent; the root itself is excluded; `[]` for a node without parents.
            'path' => $this->path(),
            // The decimal cast yields a string; the front formats a number.
            'value' => (float) $this->value,
            'semester' => $this->semester,
            'date' => $this->test_date->toDateString(),
            // Only when the query used withCount('comments').
            'comments_count' => $this->whenCounted('comments'),
        ];
    }

    /**
     * @return list<string>
     */
    private function path(): array
    {
        $names = [];
        $visited = [$this->averageNode->id => true];
        $node = $this->averageNode->parent;

        while ($node !== null && ! isset($visited[$node->id])) {
            $visited[$node->id] = true;
            $names[] = $node->name;
            $node = $node->parent;
        }

        // The last collected name is the root: drop it, then order domain -> direct parent.
        array_pop($names);

        return array_reverse($names);
    }
}
