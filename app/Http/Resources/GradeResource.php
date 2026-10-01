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
     * Eager-load path for the graded node and its ancestors.
     *
     * In the seeded IT/EC trees a leaf is at most 3 levels below the root
     * (leaf -> subdomain/variant -> domain -> root). The 4th `parents` level
     * loads the root's (empty) parents, so the ancestor walk knows it reached
     * the top without lazy loading. A deeper tree still resolves correctly,
     * only through lazy loads.
     */
    public const array RELATIONS = [
        'domain.parents.parents.parents.parents',
        'subject.subjectCategory',
        'apprenticeshipPeriod',
    ];

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $parent = $this->firstParent($this->domain);

        return [
            'id' => $this->id,
            'title' => $this->domain->name,
            'subject' => $this->subject->subjectCategory->name,
            // Ancestor names from the top-level domain (child of the root) down to the direct parent; the root itself is excluded; `[]` for a node without parents.
            'path' => $this->path(),
            // The decimal cast yields a string; the front formats a number.
            'value' => (float) $this->value,
            'semester' => $this->apprenticeshipPeriod->semester,
            'date' => $this->test_date->format('d.m.Y'),
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

    private function firstParent(Domain $domain): ?Domain
    {
        /** @var Domain|null */
        return $domain->parents->first();
    }
}
