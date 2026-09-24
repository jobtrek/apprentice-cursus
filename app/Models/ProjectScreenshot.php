<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Stored on the default (private) disk and served through
 * DossierController::screenshot, never from a public URL.
 *
 * @property int $id
 * @property int $project_id
 * @property string $path
 */
#[Fillable(['path'])]
class ProjectScreenshot extends Model
{
    protected static function booted(): void
    {
        // Only remove the file once the row deletion is committed, so a rolled
        // back transaction never leaves a row pointing at a missing file.
        static::deleted(fn (self $screenshot) => DB::afterCommit(
            fn () => Storage::delete($screenshot->path),
        ));
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
