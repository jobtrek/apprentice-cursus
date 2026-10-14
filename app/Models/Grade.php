<?php

namespace App\Models;

use App\Support\Gradebook\Semester;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Comments go away with their grade through the comments.grade_id cascade.
 *
 * @property int $id
 * @property int $user_id
 * @property int $average_node_id
 * @property string $value decimal cast: string at runtime, not float
 * @property CarbonImmutable $test_date
 * @property string|null $file_path
 * @property CarbonImmutable|null $notified_at
 * @property-read int $semester Derived from test_date, see Semester
 */
#[Fillable(['user_id', 'average_node_id', 'value', 'test_date', 'file_path'])]
class Grade extends Model
{
    protected function casts(): array
    {
        return [
            'value' => 'decimal:1',
            'test_date' => 'date',
            'notified_at' => 'datetime',
        ];
    }

    /**
     * Selects the derived semester in the same query, so a list of grades does not
     * run one query per grade through the accessor below.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function withSemester(Builder $query): void
    {
        $query->select('grades.*')->selectRaw(Semester::SQL.' AS semester');
    }

    /**
     * From withSemester() when the query used it; computed on the spot otherwise.
     *
     * @return Attribute<int, never>
     */
    protected function semester(): Attribute
    {
        return Attribute::get(fn (mixed $value): int => $value !== null
            ? (int) $value
            : Semester::of(
                $this->test_date,
                CarbonImmutable::parse(self::query()->where('user_id', $this->user_id)->min('test_date')),
            ));
    }

    public function fileName(): string
    {
        $semester = $this->semester;

        $number = self::query()
            ->withSemester()
            ->where('user_id', $this->user_id)
            ->where('average_node_id', $this->average_node_id)
            ->orderBy('test_date')
            ->orderBy('id')
            ->get()
            ->filter(fn (self $grade): bool => $grade->semester === $semester)
            ->values()
            ->search(fn (self $grade): bool => $grade->id === $this->id);

        $value = rtrim(rtrim($this->value, '0'), '.');

        $parts = [$semester, (int) $number + 1, $this->averageNode->name, $value, $this->user->name];

        return str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-', implode('_', $parts)).'.pdf';
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<AverageNode, $this>
     */
    public function averageNode(): BelongsTo
    {
        return $this->belongsTo(AverageNode::class);
    }

    /**
     * @return HasMany<Comment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }
}
