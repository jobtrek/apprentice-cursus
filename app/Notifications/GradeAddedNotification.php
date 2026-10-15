<?php

namespace App\Notifications;

use App\Models\Grade;

/**
 * Sent to the coach and trainers of an apprentice who added a grade.
 *
 * Dispatched by GradeController::store.
 */
class GradeAddedNotification extends GradeActivityNotification
{
    public function __construct(Grade $grade)
    {
        $grade->loadMissing('user', 'averageNode');

        parent::__construct(
            $grade->user,
            $grade,
            'a ajouté une note',
            route('apprentices.grades.show', [$grade->user, $grade], absolute: false),
        );
    }
}
