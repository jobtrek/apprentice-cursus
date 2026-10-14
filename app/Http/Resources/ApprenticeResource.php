<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Matches the `Apprentice` type in resources/js/types/apprentice.ts.
 *
 * Expects `ApprenticeResource::RELATIONS` to be eager loaded.
 *
 * @mixin User
 */
class ApprenticeResource extends JsonResource
{
    public const RELATIONS = ['formationPlan.trainers', 'coach', 'roles'];

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'track' => $this->formationPlan?->shortName(),
            // Filled in by ApprenticeList from the grade dates; null elsewhere.
            'year' => null,
            'isActive' => $this->is_active,
            'isMp' => $this->formationPlan?->isMp() ?? false,
            'coach' => $this->coach?->name,
            // Trainers follow the whole plan: every trainer of the apprentice's plan.
            'trainer' => $this->formationPlan?->trainers->pluck('name')->sort()->implode(', ') ?: null,
            // Lets the local admin's select show the current coach.
            'coachId' => $this->coach_id,
            'canView' => $request->user()?->can('view', $this->resource) ?? false,
        ];
    }
}
