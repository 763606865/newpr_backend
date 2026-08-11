<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('attendance:generate-schedules')->dailyAt('00:05')->withoutOverlapping()->onOneServer();
Schedule::command('rc:sync-view-stats')->dailyAt('00:30')->withoutOverlapping()->onOneServer();
Schedule::command('rc:resumes:sync-from:jucai-dt')->dailyAt('01:00')->withoutOverlapping()->onOneServer();
Schedule::command('rc:recruitment-details:sync-from:cjwl')->dailyAt('01:30')->withoutOverlapping()->onOneServer();
Schedule::command('rc:orders:cancel-expired')->everyMinute()->withoutOverlapping()->onOneServer();
