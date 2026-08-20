<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Level 3 scheduled jobs (FR-NOT-03, FR-RPT-03)
|--------------------------------------------------------------------------
|
| bootstrap/app.php routes console commands through this file
| (commands: __DIR__.'/../routes/console.php'), and there is no
| app/Console/Kernel.php in this project to hold a schedule() method — so
| this is the correct place for the schedule, per Laravel 11+ convention.
| Requires a real cron entry running `php artisan schedule:run` every
| minute on the server (Laravel Forge sets this up automatically).
*/
Schedule::command('bookings:send-reminders')->dailyAt('09:00');
Schedule::command('reports:weekly-summary')->weeklyOn(1, '08:00'); // Mondays
