<?php

namespace App\Notifications;

use App\Models\Comment;
use App\Models\Grade;

/**
 * Sent to the apprentice when a coach or trainer comments on one of their grades.
 */
class GradeCommentedNotification extends GradeActivityNotification
{
    public function __construct(Comment $comment, Grade $grade)
    {
        $grade->loadMissing('averageNode');

        parent::__construct(
            $comment->author,
            $grade,
            'a commenté votre note',
            route('grades.show', $grade, absolute: false),
        );
    }
}
