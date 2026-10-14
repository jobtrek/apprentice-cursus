<?php

namespace App\Notifications;

use App\Models\Grade;

/**
 * Sent to the coach and trainers of an apprentice who deleted a grade. Build it before
 * the delete, since it snapshots the grade's name; it carries no link.
 *
 * Not dispatched yet, like GradeAddedNotification.
 *
 *     Notification::send($apprentice->supervisors(), new GradeDeletedNotification($grade));
 */
class GradeDeletedNotification extends GradeActivityNotification
{
    public function __construct(Grade $grade)
    {
        $grade->loadMissing('user', 'averageNode');

        parent::__construct($grade->user, $grade, 'a supprimé une note', null);
    }
}
