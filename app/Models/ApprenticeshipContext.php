<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property bool $is_mp
 * @property int $apprenticeship_id
 * @property int $root_domain_id
 */
#[Fillable(['is_mp', 'apprenticeship_id', 'root_domain_id'])]
class ApprenticeshipContext extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'is_mp' => 'boolean',
        ];
    }

    /** @return BelongsTo<Apprenticeship, $this> */
    public function apprenticeship(): BelongsTo
    {
        return $this->belongsTo(Apprenticeship::class);
    }

    /** @return BelongsTo<Domain, $this> */
    public function rootDomain(): BelongsTo
    {
        return $this->belongsTo(Domain::class, 'root_domain_id');
    }

    /** @return HasMany<User, $this> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** @return HasMany<DomainEdge, $this> */
    public function domainEdges(): HasMany
    {
        return $this->hasMany(DomainEdge::class);
    }
}
