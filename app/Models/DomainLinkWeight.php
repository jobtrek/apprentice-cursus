<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Composite primary key (apprenticeship_context_id, domain_link_id). Eloquent has
 * no composite-key support: write with upsert() or a scoped query update(),
 * never save() on an existing instance.
 *
 * @property int $apprenticeship_context_id
 * @property int $domain_link_id
 * @property string $weight Decimal cast: string at runtime.
 */
class DomainLinkWeight extends Model
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
