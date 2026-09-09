<?php

namespace App\Models;

use App\Enums\AggregationType;
use App\Enums\EvaluationVariant;
use App\Enums\PeriodScope;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

/**
 * Composite pattern component: an evaluation node is either a leaf
 * (aggregation === null, tied to a subject, carries grades directly)
 * or a composite (aggregation set, combines its children's values).
 *
 * @property int $id
 * @property int|null $subject_id
 * @property string $name
 * @property AggregationType|null $aggregation
 * @property float|null $rounding_step
 * @property PeriodScope $period_scope
 * @property EvaluationVariant|null $variant
 */
#[Fillable(['subject_id', 'name', 'aggregation', 'rounding_step', 'period_scope', 'variant'])]
class EvaluationNode extends Model
{
    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'aggregation' => AggregationType::class,
            'period_scope' => PeriodScope::class,
            'variant' => EvaluationVariant::class,
            'rounding_step' => 'decimal:1',
        ];
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * The child nodes that feed into this node's value, keyed by their weight within this parent.
     */
    public function children(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'evaluation_node_connections', 'parent_id', 'child_id')
            ->using(EvaluationNodeConnection::class)
            ->withPivot(['weight', 'created_at']);
    }

    /**
     * The composite node(s) this node contributes to.
     */
    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'evaluation_node_connections', 'child_id', 'parent_id')
            ->using(EvaluationNodeConnection::class)
            ->withPivot(['weight', 'created_at']);
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }

    public function evaluationResults(): HasMany
    {
        return $this->hasMany(EvaluationResult::class);
    }

    /**
     * A leaf node has no aggregation strategy and no children; it carries grades directly.
     */
    public function isLeaf(): bool
    {
        if ($this->aggregation !== null) {
            return false;
        }

        return $this->relationLoaded('children')
            ? $this->children->isEmpty()
            : $this->children()->doesntExist();
    }

    /**
     * Attach a child under this node. A leaf (aggregation === null) can never gain
     * children — mirrors a Composite pattern's Leaf rejecting add(), keeping isLeaf()
     * and "has children" from ever contradicting each other.
     */
    public function addChild(self $child, float $weight): void
    {
        if ($this->aggregation === null) {
            throw new LogicException("Cannot attach a child to leaf node [{$this->id}]: set an aggregation strategy first.");
        }

        $this->children()->attach($child->id, ['weight' => $weight, 'created_at' => now()]);
    }
}
