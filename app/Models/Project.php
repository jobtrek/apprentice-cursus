<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string|null $organization
 * @property string $description
 * @property string|null $responsibilities
 * @property string|null $technologies
 * @property string|null $repository_url
 * @property string|null $demo_path
 * @property CarbonImmutable $date_start
 * @property CarbonImmutable|null $date_end
 */
#[Fillable([
    'user_id', 'title', 'organization', 'description', 'responsibilities',
    'technologies', 'repository_url', 'demo_path', 'date_start', 'date_end',
])]
class Project extends Model
{
    protected static function booted(): void
    {
        static::deleting(fn (self $project) => $project->comments()->delete());
    }

    /**
     * Same reasoning as Grade::delete(): the polymorphic comments are removed by the
     * booted() hook, and the transaction is what keeps the two deletes atomic.
     */
    public function delete(): ?bool
    {
        return DB::transaction(fn (): ?bool => parent::delete());
    }

    protected function casts(): array
    {
        return [
            'date_start' => 'date',
            'date_end' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'project_skill');
    }
}
