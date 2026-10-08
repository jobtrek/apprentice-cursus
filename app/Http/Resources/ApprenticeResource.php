<?php

namespace App\Http\Resources;

use App\Models\User;
use Database\Seeders\ApprenticeshipSeeder;
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
    public const RELATIONS = ['apprenticeship', 'apprenticeshipContext', 'coach', 'trainer', 'roles'];

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $track = match ($this->apprenticeship?->name) {
            ApprenticeshipSeeder::IT => 'IT',
            ApprenticeshipSeeder::EC => 'EC',
            default => null,
        };

        return [
            'id' => $this->id,
            'name' => $this->name,
            'track' => $track,
            // Derived from the grades once the grade calculation lands; null until then.
            'year' => null,
            'isActive' => $this->is_active,
            'coach' => $this->coach?->name,
            'trainer' => $this->trainer?->name,
            // Let the local admin's selects show the current coach and trainer.
            'coachId' => $this->coach_id,
            'trainerId' => $this->trainer_id,
            'canView' => $request->user()?->can('view', $this->resource) ?? false,
        ];
    }
}
