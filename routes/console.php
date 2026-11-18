<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Scheduled jobs
|--------------------------------------------------------------------------
|
| bootstrap/app.php routes console commands through this file (Laravel 11+
| convention — there is no app/Console/Kernel.php). Requires a cron entry
| running `php artisan schedule:run` every minute (Forge sets this up).
|
*/

Schedule::command('bookings:send-reminders')->dailyAt('09:00');                    // FR-NOT-03
Schedule::command('reports:weekly-summary')->weeklyOn(1, '08:00');                 // FR-RPT-03, Mondays
Schedule::command('bookings:expire-unpaid')->everyFiveMinutes()->withoutOverlapping(); // payment holds
