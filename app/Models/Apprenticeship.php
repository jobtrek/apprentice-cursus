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
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
