<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int $id
 * @property int $parent_id
 * @property int $child_id
 * @property float $weight
 */
class EvaluationNodeConnection extends Pivot
{
    const UPDATED_AT = null;

    protected $table = 'evaluation_node_connections';

    public $incrementing = true;

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
        ];
    }
}
