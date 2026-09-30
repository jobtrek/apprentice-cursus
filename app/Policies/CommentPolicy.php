<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\Grade;
use App\Models\Project;
use App\Models\User;

class CommentPolicy
{
    public function update(User $user, Comment $comment): bool
    {
        return $this->isAuthorOfActiveApprenticeComment($user, $comment);
    }

    public function delete(User $user, Comment $comment): bool
    {
        return $this->isAuthorOfActiveApprenticeComment($user, $comment);
    }

    /**
     * Only the author may change a comment, and only while the apprentice it
     * is attached to (grade or project owner) is still active.
     */
    private function isAuthorOfActiveApprenticeComment(User $user, Comment $comment): bool
    {
        $commentable = $comment->commentable;
        $owner = $commentable instanceof Grade || $commentable instanceof Project ? $commentable->user : null;

        return $comment->author_id === $user->id && $owner?->is_active === true;
    }
}
