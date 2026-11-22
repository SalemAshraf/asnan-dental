<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Queue due recall/appointment reminders for manual one-tap sending.
// Detection and queueing only - no automated messages are ever sent.
Schedule::command('reminders:queue')->hourly()->withoutOverlapping();
