<?php

use App\Jobs\SyncAzureAccounts;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new SyncAzureAccounts)->daily()->onOneServer();

// withoutOverlapping: a slow IMAP run must not start a second import of the same unread mails.
Schedule::command('emails:fetch')->everyFiveMinutes()->withoutOverlapping()->onOneServer();
