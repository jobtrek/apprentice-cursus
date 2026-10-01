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
    public const RELATIONS = ['apprenticeship', 'coach', 'roles'];

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'track' => match ($this->apprenticeship?->name) {
                ApprenticeshipSeeder::IT => 'IT',
                ApprenticeshipSeeder::EC => 'EC',
                default => null,
            },
            // Derived from the grades once the grade calculation lands; null until then.
            'year' => null,
            'coach' => $this->coach?->name,
            // Coaches list apprentices they do not coach yet but cannot open them.
            'canView' => $request->user()?->can('view', $this->resource) ?? false,
            'canAssign' => $request->user()?->can('assignSelf', $this->resource) ?? false,
        ];
    }
}
