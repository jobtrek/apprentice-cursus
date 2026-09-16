<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property int $author_id
 * @property string $commentable_type
 * @property int $commentable_id
 * @property string $body
 */
/*
 * author_id is deliberately not fillable: it always comes from the authenticated user,
 * never from a request payload. Build the comment, associate the author, then save it
 * through the relation — create() would insert before the author is set and hit the
 * NOT NULL constraint:
 *
 *     $comment = new Comment($request->validated());
 *     $comment->author()->associate($request->user());
 *     $grade->comments()->save($comment);
 */
#[Fillable(['body'])]
class Comment extends Model
{
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }
}
