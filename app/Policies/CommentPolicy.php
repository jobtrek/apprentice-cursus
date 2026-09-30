<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\Grade;
use App\Models\Project;
use App\Models\User;

/**
 * role_permissions.md — "Feedback / Comments". Authorize creation with the target:
 * Gate::authorize('create', [Comment::class, $grade]).
 */
class CommentPolicy
{
    /**
     * Trainers (section) and coaches (all) comment on an active apprentice's grade or project.
     */
    public function create(User $user, Grade|Project $commentable): bool
    {
        return ($user->isTrainer() || $user->isCoach())
            && $user->canSeeApprentice($commentable->user)
            && $commentable->user->is_active;
    }

    public function view(User $user, Comment $comment): bool
    {
        $commentable = $comment->commentable;

        return ($commentable instanceof Grade || $commentable instanceof Project)
            && $user->canSeeApprentice($commentable->user);
    }

    /**
     * Only the author edits or deletes a comment, never someone else's.
     */
    public function update(User $user, Comment $comment): bool
    {
        return $user->id === $comment->author_id;
    }

    public function delete(User $user, Comment $comment): bool
    {
        return $this->update($user, $comment);
    }
}
