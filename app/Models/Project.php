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
 * @property string|null $demo_url
 * @property CarbonImmutable $date_start
 * @property CarbonImmutable|null $date_end
 */
#[Fillable([
    'user_id', 'title', 'organization', 'description', 'responsibilities',
    'technologies', 'repository_url', 'demo_url', 'date_start', 'date_end',
])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    public $timestamps = false;

    protected static function booted(): void
    {
        // One by one rather than relying on the FK cascade, so each screenshot's
        // file is removed too (see ProjectScreenshot::booted()). Comments go
        // through the comments.project_id cascade.
        static::deleting(fn (self $project) => $project->screenshots->each->delete());
    }

    /**
     * The transaction keeps the screenshots' deletes and the project's own atomic:
     * without it, a failure on the project's DELETE would leave its screenshots gone.
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

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Comment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * @return HasMany<ProjectScreenshot, $this>
     */
    public function screenshots(): HasMany
    {
        return $this->hasMany(ProjectScreenshot::class);
    }

    /**
     * @return BelongsToMany<Skill, $this>
     */
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'project_skill');
    }
}
