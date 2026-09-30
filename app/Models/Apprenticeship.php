<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 */
#[Fillable(['code', 'name'])]
class Apprenticeship extends Model
{
    public const IT = 'it';

    public const EC = 'ec';

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
