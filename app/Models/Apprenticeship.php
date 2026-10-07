<?php

namespace App\Models;

use Database\Seeders\ApprenticeshipSeeder;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 */
#[Fillable(['name'])]
class Apprenticeship extends Model
{
    /** @return HasMany<ApprenticeshipContext, $this> */
    public function contexts(): HasMany
    {
        return $this->hasMany(ApprenticeshipContext::class);
    }

    /** Short label of the section: "IT", "EC", or the full name for another one. */
    public function shortName(): string
    {
        return match ($this->name) {
            ApprenticeshipSeeder::IT => 'IT',
            ApprenticeshipSeeder::EC => 'EC',
            default => $this->name,
        };
    }
}
