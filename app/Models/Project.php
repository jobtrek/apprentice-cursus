<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

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
 * @property Carbon $date_start
 * @property Carbon|null $date_end
 */
#[Fillable([
    'user_id', 'title', 'organization', 'description', 'responsibilities',
    'technologies', 'repository_url', 'demo_path', 'date_start', 'date_end',
])]
class Project extends Model
{
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
