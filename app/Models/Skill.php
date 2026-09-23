<?php

namespace App\Models;

use Database\Factories\SkillFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $name
 */
#[Fillable(['name'])]
class Skill extends Model
{
    /** @use HasFactory<SkillFactory> */
    use HasFactory;

    const UPDATED_AT = null;

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_skill');
    }
}
