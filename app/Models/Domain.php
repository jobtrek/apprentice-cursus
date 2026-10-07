<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $rounding_step Decimal cast: string at runtime.
 */
#[Fillable(['name', 'rounding_step'])]
class Domain extends Model
{
    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'rounding_step' => 'decimal:1',
        ];
    }

    /** @return BelongsToMany<Domain, $this> */
    public function children(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'domain_links', 'parent_id', 'child_id');
    }

    /** @return BelongsToMany<Domain, $this> */
    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'domain_links', 'child_id', 'parent_id');
    }

    /** @return HasMany<DomainLink, $this> */
    public function childLinks(): HasMany
    {
        return $this->hasMany(DomainLink::class, 'parent_id');
    }

    /** @return HasMany<DomainLink, $this> */
    public function parentLinks(): HasMany
    {
        return $this->hasMany(DomainLink::class, 'child_id');
    }

    /** @return HasMany<Subject, $this> */
    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class);
    }

    /** @return HasMany<Grade, $this> */
    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }

    /** @return HasMany<ApprenticeshipContext, $this> */
    public function apprenticeshipContexts(): HasMany
    {
        return $this->hasMany(ApprenticeshipContext::class, 'root_domain_id');
    }
}
