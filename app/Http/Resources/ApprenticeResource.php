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
    /** Only the columns the resource reads, to keep the lists light. */
    public const RELATIONS = ['apprenticeship:id,name', 'coach:id,name'];

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            // The front only knows the short codes, not the full apprenticeship names.
            'apprenticeship' => match ($this->apprenticeship?->name) {
                ApprenticeshipSeeder::IT => 'IT',
                ApprenticeshipSeeder::EC => 'EC',
                default => null,
            },
            'coach' => $this->coach?->name,
            'isActive' => $this->is_active,
        ];
    }
}
