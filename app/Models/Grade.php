<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property int $user_id
 * @property int $evaluation_node_id
 * @property string $value decimal cast: string at runtime, not float
 * @property CarbonImmutable $test_date
 * @property int $semester
 * @property string|null $file_path
 * @property string|null $original_filename
 * @property CarbonImmutable|null $notified_at
 */
#[Fillable(['user_id', 'evaluation_node_id', 'value', 'test_date', 'semester', 'file_path', 'original_filename'])]
class Grade extends Model
{
    protected static function booted(): void
    {
        static::deleting(fn (self $grade) => $grade->comments()->delete());
    }

    /**
     * A comment's target is polymorphic, so no foreign key can cascade it away with its
     * grade — the booted() hook above does it instead. Wrapping the whole delete in a
     * transaction is what makes the pair atomic: without it, a failure on the grade's own
     * DELETE would leave the comments already gone with nothing to roll them back.
     *
     * Only fires on Eloquent deletes. Bulk deletes (DB::table(...)->delete(),
     * Grade::where(...)->delete()) bypass both and must clean up comments themselves.
     */
    public function delete(): ?bool
    {
        return DB::transaction(fn (): ?bool => parent::delete());
    }

    protected function casts(): array
    {
        return [
            'value' => 'decimal:1',
            'test_date' => 'date',
            'notified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function evaluationNode(): BelongsTo
    {
        return $this->belongsTo(EvaluationNode::class);
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }
}
