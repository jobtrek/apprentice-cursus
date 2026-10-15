<?php

namespace App\Notifications;

use App\Models\Grade;
use LogicException;

/**
 * Sent to the coach and trainers of an apprentice who deleted a grade. Build it before
 * the delete, since it snapshots the grade's name; it carries no link.
 *
 * Dispatched by GradeController::destroy.
 */
class GradeDeletedNotification extends GradeActivityNotification
{
    public function __construct(Grade $grade)
    {
        $grade->loadMissing('user', 'averageNode');

        parent::__construct($grade->user ?? throw new LogicException('Grade has no apprentice.'), $grade, 'a supprimé une note', null);
    }
}
