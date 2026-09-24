<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property int|null $evaluation_node_id Root of the apprenticeship's grade tree.
 */
#[Fillable(['name', 'evaluation_node_id'])]
class Apprenticeship extends Model
{
    /**
     * Root node of the grade tree the apprenticeship's apprentices are evaluated on.
     *
     * @return BelongsTo<EvaluationNode, $this>
     */
    public function evaluationTree(): BelongsTo
    {
        return $this->belongsTo(EvaluationNode::class, 'evaluation_node_id');
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
