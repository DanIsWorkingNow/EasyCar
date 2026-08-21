<?php

namespace App\Providers;

use Illuminate\Auth\Events\Failed;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

/**
 * SUPERSEDES the AppServiceProvider shipped in Level 3 (HTTPS enforcement,
 * failed-login logging, the 'login' limiter). Adds two new named limiters
 * — 'api' and 'api-write' — per TSD Section 8.11.2, now that TD-23's fix
 * (bootstrap/app.php, this kit) makes routes/api.php reachable for the
 * first time and gives these something to actually attach to.
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

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip().'|'.$request->input('email'));
        });

        // General ceiling for any API request — reads and writes both count
        // against this. Per-user once authenticated (Sanctum), so one
        // customer's dashboard polling can't starve another's; per-IP for
        // the one unauthenticated route (/auth/login itself is additionally
        // covered by the stricter 'login' limiter above).
        RateLimiter::for('api', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(120)->by($request->user()->id)
                : Limit::perMinute(30)->by($request->ip());
        });

        // Stacked on top of 'api' (not a replacement for it) for anything
        // that mutates data — bookings, cars, users. A compromised token or
        // a buggy client can only write so fast, even within its 'api'
        // budget for reads.
        RateLimiter::for('api-write', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });

        Event::listen(Failed::class, function (Failed $event) {
            Log::channel('single')->warning('Failed login attempt', [
                'email' => $event->credentials['email'] ?? null,
                'ip' => request()->ip(),
            ]);
        });
    }
}
