<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $parent_id
 * @property int $child_id
 */
class DomainLink extends Model
{
    protected $table = 'domain_links';

    public $timestamps = false;

    /** @return BelongsTo<Domain, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Domain::class, 'parent_id');
    }

    /** @return BelongsTo<Domain, $this> */
    public function child(): BelongsTo
    {
        return $this->belongsTo(Domain::class, 'child_id');
    }

    /** @return HasMany<DomainLinkWeight, $this> */
    public function weights(): HasMany
    {
        return $this->hasMany(DomainLinkWeight::class, 'domain_link_id');
    }
}
