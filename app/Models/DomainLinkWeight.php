<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int $apprenticeship_context_id
 * @property int $domain_link_id
 * @property string $weight Decimal cast: string at runtime.
 */
class DomainLinkWeight extends Pivot
{
    protected $table = 'domain_link_weights';

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

    /** @return BelongsTo<DomainLink, $this> */
    public function domainLink(): BelongsTo
    {
        return $this->belongsTo(DomainLink::class);
    }
}
