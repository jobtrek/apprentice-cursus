<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $evaluation_node_id
 * @property int $semester
 * @property float $rounded_value
 */
#[Fillable(['user_id', 'evaluation_node_id', 'semester', 'rounded_value'])]
class EvaluationResult extends Model
{
    protected function casts(): array
    {
        return [
            'rounded_value' => 'decimal:1',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function evaluationNode(): BelongsTo
    {
        return $this->belongsTo(EvaluationNode::class);
    }
}
