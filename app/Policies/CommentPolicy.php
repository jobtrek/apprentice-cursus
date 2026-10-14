<?php

namespace App\Policies;

use App\Models\Comment;
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
     * Only the author may change a comment, only while the apprentice it is
     * attached to (grade or project owner) is still active, and, for a grade,
     * only while the author can still view it (a reviewer who lost supervision
     * can no longer change their feedback).
     */
    private function isAuthorOfActiveApprenticeComment(User $user, Comment $comment): bool
    {
        $owner = ($comment->grade ?? $comment->project)?->user;

        if ($comment->grade !== null && ! $user->can('view', $comment->grade)) {
            return false;
        }

        return $comment->author_id === $user->id && $owner?->is_active === true;
    }
}
