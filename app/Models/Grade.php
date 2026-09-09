<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $evaluation_node_id
 * @property float $value
 * @property Carbon $test_date
 * @property int $semester
 * @property string|null $file_path
 * @property string|null $original_filename
 * @property Carbon|null $notified_at
 */
#[Fillable(['user_id', 'evaluation_node_id', 'value', 'test_date', 'semester', 'file_path', 'original_filename'])]
class Grade extends Model
{
    protected static function booted(): void
    {
        static::deleting(fn (self $grade) => $grade->comments()->delete());
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
