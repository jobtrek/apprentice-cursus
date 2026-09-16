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
class SubjectCategory extends Model
{
    protected $table = 'subject_category';

    public $timestamps = false;

    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class);
    }
}
