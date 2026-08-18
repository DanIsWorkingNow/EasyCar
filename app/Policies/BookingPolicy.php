<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

/**
 * Registers under Booking::class in AuthServiceProvider's $policies array.
 * Staff are scoped to their own branch; admin passes every check via the
 * Gate::before() super-admin bypass in AuthServiceProvider.
 */
class BookingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('approve-booking');
    }

    public function view(User $user, Booking $booking): bool
    {
        if ($user->id === $booking->user_id) {
            return true; // a customer can always view their own booking
        }

        if (! $user->can('approve-booking')) {
            return false;
        }

        if ($user->hasRole('staff')) {
            return $booking->cars->contains('branch_id', $user->branch_id);
        }

        return true; // admin
    }

    public function approve(User $user, Booking $booking): bool
    {
        return $user->id !== $booking->user_id && $this->view($user, $booking);
    }

    public function reject(User $user, Booking $booking): bool
    {
        return $this->approve($user, $booking);
    }

    public function viewDashboard(User $user): bool
    {
        return $user->can('view-dashboard');
    }
}
