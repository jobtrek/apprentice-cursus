<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $subject_category_id
 */
#[Fillable(['domain_id', 'subject_category_id'])]
class Subject extends Model
{
    const UPDATED_AT = null;

    /** @return BelongsTo<SubjectCategory, $this> */
    public function subjectCategory(): BelongsTo
    {
        return $this->belongsTo(SubjectCategory::class);
    }

    /** @return BelongsTo<Domain, $this> */
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    /** @return HasMany<Grade, $this> */
    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }
}
