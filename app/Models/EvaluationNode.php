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
 * @property string|null $rounding_step decimal cast: string at runtime, not float
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
            ->withPivot(['id', 'weight', 'created_at']);
    }

    /**
     * The composite node(s) this node contributes to.
     */
    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'evaluation_node_connections', 'child_id', 'parent_id')
            ->using(EvaluationNodeConnection::class)
            ->withPivot(['id', 'weight', 'created_at']);
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
     * A leaf node has no aggregation strategy; it carries grades directly.
     *
     * addChild() guarantees a node without an aggregation strategy can never gain
     * children, so the absence of an aggregation is enough — no query needed.
     */
    public function isLeaf(): bool
    {
        return $this->aggregation === null;
    }

    /**
     * Attach a child under this node.
     *
     * Three invariants are enforced here, because the schema cannot enforce any of them:
     * - a leaf (aggregation === null) never gains children, so isLeaf() and "has children"
     *   can never contradict each other;
     * - a node is never its own child;
     * - the edge never closes a cycle. The graph is a DAG, and any recursive aggregation
     *   walk would loop forever on a cycle.
     *
     * @throws LogicException when the edge would break one of them.
     */
    public function addChild(self $child, float $weight): void
    {
        if ($this->aggregation === null) {
            throw new LogicException("Cannot attach a child to leaf node [{$this->id}]: set an aggregation strategy first.");
        }

        if ($child->id === $this->id) {
            throw new LogicException("Cannot attach node [{$this->id}] to itself.");
        }

        if ($child->hasDescendant($this)) {
            throw new LogicException("Cannot attach node [{$child->id}] under [{$this->id}]: it would create a cycle.");
        }

        $this->children()->attach($child->id, ['weight' => $weight]);
    }

    /**
     * Whether $node is reachable by walking down from this node.
     *
     * Breadth-first over evaluation_node_connections, with a visited set so an already
     * corrupted graph makes this return instead of looping.
     */
    public function hasDescendant(self $node): bool
    {
        $frontier = [$this->id];
        $visited = [];

        while ($frontier !== []) {
            $visited = array_merge($visited, $frontier);

            /** @var list<int> $frontier */
            $frontier = EvaluationNodeConnection::query()
                ->whereIn('parent_id', $frontier)
                ->pluck('child_id')
                ->map(fn (int|string $id): int => (int) $id)
                ->reject(fn (int $id): bool => in_array($id, $visited, true))
                ->unique()
                ->values()
                ->all();

            if (in_array($node->id, $frontier, true)) {
                return true;
            }
        }

        return false;
    }
}
