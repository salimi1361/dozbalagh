<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

require __DIR__.'/cmr_console.php';

Schedule::command('permits:send-expiry-reminders')
    ->dailyAt('09:00')
    ->withoutOverlapping();
