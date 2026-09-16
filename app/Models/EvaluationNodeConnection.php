<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int $id
 * @property int $parent_id
 * @property int $child_id
 * @property string $weight decimal cast: string at runtime, not float
 */
class EvaluationNodeConnection extends Pivot
{
    protected $table = 'evaluation_node_connections';

    public $incrementing = true;

    /**
     * Pivot already defaults to false; stated explicitly because the table has a
     * created_at and no updated_at. created_at is filled by the column's DB default,
     * so any attach() gets one, not just the ones going through addChild().
     */
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
        ];
    }
}
