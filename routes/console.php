<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// TimeBudget reminder scheduler (run every minute when cron calls schedule:run)
Schedule::command('reminders:shift')->everyMinute();
Schedule::command('reminders:custom')->everyMinute();
