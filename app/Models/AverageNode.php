<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

/**
 * A node of a grade tree. A leaf carries grades directly; any other node averages its
 * children, each weighted by its own `weight`. The root (no parent) is the final grade
 * of the formation plans that use it.
 *
 * @property int $id
 * @property int|null $parent_id
 * @property string $name
 * @property string|null $rounding_step decimal cast: string at runtime, not float
 * @property string|null $weight decimal cast: fraction of the parent's average, NULL on a root
 */
#[Fillable(['parent_id', 'name', 'rounding_step', 'weight'])]
class AverageNode extends Model
{
    public $timestamps = false;

    protected static function booted(): void
    {
        static::saving(function (self $node): void {
            if ($node->isDirty('parent_id') && $node->parent_id !== null) {
                $node->guardAgainstCycle();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'rounding_step' => 'decimal:1',
            'weight' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<self, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<self, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * @return HasMany<Grade, $this>
     */
    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }

    /**
     * The plans this node is part of. An MP plan and a standard plan share every node
     * except their own variant branch.
     *
     * @return BelongsToMany<FormationPlan, $this>
     */
    public function formationPlans(): BelongsToMany
    {
        return $this->belongsToMany(FormationPlan::class, 'average_node_formation_plan');
    }

    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }

    /** Uses the loaded children when there are, so a tree walk stays free of N+1. */
    public function isLeaf(): bool
    {
        return $this->relationLoaded('children')
            ? $this->children->isEmpty()
            : ! $this->children()->exists();
    }

    /**
     * The schema only blocks a node being its own parent; a longer loop (A -> B -> A)
     * would make every recursive average walk forever, so it is rejected here by walking
     * up from the new parent. The visited set stops the walk on an already corrupted tree.
     *
     * @throws LogicException when the new parent is this node or one of its descendants.
     */
    private function guardAgainstCycle(): void
    {
        $ancestorId = $this->parent_id;
        $visited = [];

        while ($ancestorId !== null && ! in_array($ancestorId, $visited, true)) {
            if ($ancestorId === $this->id) {
                throw new LogicException("Cannot put node [{$this->id}] under [{$this->parent_id}]: it would create a cycle.");
            }

            $visited[] = $ancestorId;
            $ancestorId = self::query()->whereKey($ancestorId)->value('parent_id');
        }
    }
}
