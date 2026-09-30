<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::deleting(function (self $project): void {
            $project->comments()->delete();
            // One by one rather than relying on the FK cascade, so each
            // screenshot's file is removed too (see ProjectScreenshot::booted()).
            $project->screenshots->each->delete();
        });
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

    /**
     * @return HasMany<ProjectScreenshot, $this>
     */
    public function screenshots(): HasMany
    {
        return $this->hasMany(ProjectScreenshot::class);
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'project_skill');
    }
}
