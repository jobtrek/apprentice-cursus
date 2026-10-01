<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

/** @property int $parent_id @property int $child_id */
class DomainNode extends Pivot
{
    protected $table = 'domain_nodes';

    public $incrementing = false;

    public $timestamps = false;

    protected $primaryKey = null;

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

    /** @return HasMany<DomainEdge, $this> */
    public function domainEdges(): HasMany
    {
        return $this->hasMany(DomainEdge::class, 'domain_node_id');
    }
}