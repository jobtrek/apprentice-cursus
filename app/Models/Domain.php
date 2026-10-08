<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

/**
 * @property int $id
 * @property string $name
 * @property string|null $rounding_step Decimal cast: string at runtime.
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

    /**
     * Attach a child while preserving the acyclic domain graph invariant.
     *
     * @throws LogicException
     */
    public function linkChild(self $child): DomainLink
    {
        if ($this->is($child)) {
            throw new LogicException('A domain cannot be linked to itself.');
        }

        $reachable = [$child->getKey()];
        $seen = [];

        while ($reachable !== []) {
            $ids = array_values(array_diff($reachable, $seen));

            if (in_array($this->getKey(), $ids, true)) {
                throw new LogicException('Linking these domains would create a cycle.');
            }

            $seen = [...$seen, ...$ids];
            $reachable = DomainLink::query()
                ->whereIn('parent_id', $ids)
                ->pluck('child_id')
                ->map(static fn (int|string $id): int => (int) $id)
                ->all();
        }

        $link = DomainLink::query()
            ->where('parent_id', $this->getKey())
            ->where('child_id', $child->getKey())
            ->first();

        if ($link !== null) {
            return $link;
        }

        $link = new DomainLink;
        $link->forceFill([
            'parent_id' => $this->getKey(),
            'child_id' => $child->getKey(),
        ])->save();

        return $link;
    }

    /** @return BelongsToMany<Subject, $this> */
    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class);
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
