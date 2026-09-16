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
#[Fillable(['subject_category_id'])]
class Subject extends Model
{
    const UPDATED_AT = null;

    public function subjectCategory(): BelongsTo
    {
        return $this->belongsTo(SubjectCategory::class);
    }

    public function evaluationNodes(): HasMany
    {
        return $this->hasMany(EvaluationNode::class);
    }
}
