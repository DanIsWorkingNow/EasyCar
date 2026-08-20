<?php

namespace App\Providers;

use Illuminate\Auth\Events\Failed;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

/**
 * Builds on the cleaned-up version of this file from Level 1 Part 2 (which
 * removed the duplicate 'admin'/'staff' middleware alias registration —
 * TD-06). Adds three Level 3 items:
 *
 *   - FR-AUTH-04: a dedicated 'login' rate limiter (stricter than the
 *     framework's default 'api' throttle), applied to the login route via
 *     LoginController's constructor (see PATCHES_LEVEL3.md).
 *   - HTTPS/HSTS enforcement in production.
 *   - FR-AUTH-06: logs every failed login attempt.
 */
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        RateLimiter::for('login', function ($request) {
            return Limit::perMinute(5)->by($request->ip().'|'.$request->input('email'));
        });

        Event::listen(Failed::class, function (Failed $event) {
            Log::channel('single')->warning('Failed login attempt', [
                'email' => $event->credentials['email'] ?? null,
                'ip' => request()->ip(),
            ]);
        });
    }
}
