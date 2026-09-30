<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Needs the server to run the scheduler (`schedule:run` every minute), see docs/project-docs/role_permissions.md.
Schedule::command('apprentices:sync')->daily()->withoutOverlapping();
