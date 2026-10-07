<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\CommentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property int $author_id
 * @property string $commentable_type
 * @property int $commentable_id
 * @property string $body
 * @property CarbonImmutable $created_at
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
    /** @use HasFactory<CommentFactory> */
    use HasFactory;

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** @return MorphTo<Model, $this> */
    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }
}
