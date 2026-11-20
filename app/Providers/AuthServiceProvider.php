<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\Car;
use App\Models\Payment;
use App\Models\User;
use App\Models\VehicleInspection;
use App\Policies\BookingPolicy;
use App\Policies\CarPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\VehicleInspectionPolicy;
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
        Payment::class => PaymentPolicy::class,
        VehicleInspection::class => VehicleInspectionPolicy::class,
    ];

    public function boot(): void
    {
        Gate::before(fn ($user, $ability) => $user->hasRole('admin') ? true : null);

        // Rental operations: staff hand over / take back only their own
        // branch's cars, and manage extras on bookings that include one.
        Gate::define('handover', fn (User $user, Car $car) => $user->hasRole('staff')
            && $user->branch_id !== null
            && $car->branch_id === $user->branch_id);

        Gate::define('manage-charges', fn (User $user, Booking $booking) => $user->hasRole('staff')
            && $user->branch_id !== null
            && $booking->cars()->where('branch_id', $user->branch_id)->exists());
    }
}
