<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Section trainers
    |--------------------------------------------------------------------------
    |
    | The trainer shown to a section's apprentices, by track, matched on the
    | name synced from Entra. Only an active trainer of that section is shown.
    |
    */

    'trainers' => [
        'IT' => env('APPRENTICESHIP_TRAINER_IT', 'Bastien Nicoud'),
        'EC' => env('APPRENTICESHIP_TRAINER_EC'),
    ],

];
