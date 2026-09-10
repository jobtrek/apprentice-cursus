<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $evaluation_node_id
 * @property int $semester Sentinel 0 when the node's period_scope is `cursus`.
 *                         Never NULL, so the (user_id, evaluation_node_id, semester) unique index stays
 *                         null-safe and recompute upserts update in place instead of duplicating.
 * @property string $rounded_value decimal cast: string at runtime, not float
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
