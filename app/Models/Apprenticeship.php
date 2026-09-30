<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string|null $code it | ec, the key Azure group mapping resolves to
 */
#[Fillable(['name', 'code'])]
class Apprenticeship extends Model
{
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
