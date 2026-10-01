<?php

namespace App\Models;

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
}
