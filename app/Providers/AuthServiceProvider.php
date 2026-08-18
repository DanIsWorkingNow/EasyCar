<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\Car;
use App\Policies\BookingPolicy;
use App\Policies\CarPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

/**
 * CUTOVER (TSD 4.4 step 3). Changes from the original:
 *  - Removed Gate::define('is-admin', ...) / Gate::define('is-staff', ...):
 *    replaced by role:admin / role:staff route middleware (Spatie) in
 *    routes/web.php, and by the Policy classes below for anything needing
 *    per-resource / per-branch logic.
 *  - Registered BookingPolicy alongside the fixed CarPolicy.
 *  - Added Gate::before() so the 'admin' role automatically passes every
 *    permission check without needing each new permission hand-added to it.
 */
class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Car::class => CarPolicy::class,
        Booking::class => BookingPolicy::class,
    ];

    public function boot(): void
    {
        Gate::before(fn ($user, $ability) => $user->hasRole('admin') ? true : null);
    }
}
