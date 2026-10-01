<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int $apprenticeship_context_id
 * @property int $domain_node_id
 * @property string $weight Decimal cast: string at runtime.
 */
class DomainEdge extends Pivot
{
    protected $table = 'domain_edges';

    public $incrementing = false;

    public $timestamps = false;

    protected $primaryKey = null;

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<ApprenticeshipContext, $this> */
    public function apprenticeshipContext(): BelongsTo
    {
        return $this->belongsTo(ApprenticeshipContext::class);
    }

    /** @return BelongsTo<DomainNode, $this> */
    public function domainNode(): BelongsTo
    {
        return $this->belongsTo(DomainNode::class);
    }
}
