<?php

namespace App\Http\Resources;

use App\Models\Domain;
use App\Models\Grade;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Matches the `Grade` type in resources/js/types/grade.ts.
 *
 * Expects `GradeResource::RELATIONS` to be eager loaded.
 *
 * @mixin Grade
 */
class GradeResource extends JsonResource
{
    /**
     * Eager-load paths: the graded domain with its ancestors, and the period
     * the semester is read from.
     *
     * In the seeded IT/EC trees a leaf is at most 3 levels below the root
     * (leaf -> subdomain -> domain -> root). The 4th `parents` level loads the
     * root's (empty) parents, so the ancestor walk knows it reached the top
     * without lazy loading. A deeper tree still resolves correctly, only
     * through lazy loads.
     */
    public const RELATIONS = ['domain.parents.parents.parents.parents', 'apprenticeshipPeriod'];

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $parent = $this->firstParent($this->domain);

        return [
            'id' => $this->id,
            'node_id' => $this->domain_id,
            'title' => $this->domain->name,
            // Name of the parent domain; empty when the domain has none.
            'subject' => $parent === null ? '' : $parent->name,
            // Ancestor names from the top-level domain (child of the root) down to the direct parent; the root itself is excluded; `[]` for a domain without parents.
            'path' => $this->path(),
            // The decimal cast yields a string; the front formats a number.
            'value' => (float) $this->value,
            'semester' => $this->apprenticeshipPeriod->semester,
            'date' => $this->test_date->format('d.m.Y'),
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
        $visited = [$this->domain->id => true];
        $node = $this->firstParent($this->domain);

        while ($node !== null && ! isset($visited[$node->id])) {
            $visited[$node->id] = true;
            $names[] = $node->name;
            $node = $this->firstParent($node);
        }

        // The last collected name is the root: drop it, then order domain -> direct parent.
        array_pop($names);

        return array_reverse($names);
    }

    /**
     * A domain has one parent in the seeded trees; if it ever has several,
     * the one with the lowest id is shown.
     */
    private function firstParent(Domain $domain): ?Domain
    {
        return $domain->parents->sortBy('id')->first();
    }
}
