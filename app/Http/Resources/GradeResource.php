<?php

namespace App\Http\Resources;

use App\Models\Grade;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Matches the `Grade` type in resources/js/types/grade.ts.
 *
 * Expects `evaluationNode.parents` to be eager loaded.
 *
 * @mixin Grade
 */
class GradeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $parent = $this->evaluationNode->parents->sortBy('pivot.id')->first();

        return [
            'id' => $this->id,
            'title' => $this->evaluationNode->name,
            // First parent by connection id; empty when the node has none.
            'subject' => $parent === null ? '' : $parent->name,
            // The decimal cast yields a string; the front formats a number.
            'value' => (float) $this->value,
            'semester' => $this->semester,
            'date' => $this->test_date->format('d.m.Y'),
        ];
    }
}
