<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 */
#[Fillable(['name'])]
class FormationPlan extends Model
{
    public const IT = 'Informaticien·ne CFC';

    public const EC = 'Employé·e de commerce CFC';

    /** Shares every EC node except the "Note d'expérience" variant branch. */
    public const EC_MP = 'Employé·e de commerce CFC — Maturité professionnelle';

    public $timestamps = false;

    /**
     * Every node of the plan's grade tree, root included.
     *
     * @return BelongsToMany<AverageNode, $this>
     */
    public function averageNodes(): BelongsToMany
    {
        return $this->belongsToMany(AverageNode::class, 'average_node_formation_plan');
    }

    /** The plan's final grade: its only node without a parent. */
    public function rootNode(): ?AverageNode
    {
        return $this->averageNodes()->whereNull('parent_id')->first();
    }

    /**
     * The apprentices following this plan.
     *
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function trainers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'users_formation_plans');
    }

    /** The maturité professionnelle variant of a section. */
    public function isMp(): bool
    {
        return $this->name === self::EC_MP;
    }

    /** Short label of the section: "IT", "EC", or the full name for another one. */
    public function shortName(): string
    {
        return match ($this->name) {
            self::IT => 'IT',
            self::EC, self::EC_MP => 'EC',
            default => $this->name,
        };
    }
}
