<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Consolidates the car-availability conflict query that previously existed
 * as three near-identical copies inside BookingController::store(),
 * update(), and getAvailableCars() (TD-14). Booking::approve() also calls
 * hasConflict() before transitioning to "approved", closing the race
 * condition where two overlapping pending bookings for the same car could
 * both previously be approved (TD-04).
 */
class BookingAvailabilityService
{
    /**
     * Whether $carId has any conflicting, non-cancelled booking overlapping
     * [$start, $end]. Pass $excludeBookingId when checking a booking being
     * edited, so it doesn't conflict with itself.
     */
    public function hasConflict(int $carId, string $start, string $end, ?int $excludeBookingId = null): bool
    {
        return DB::table('car_booking')
            ->join('bookings', 'bookings.id', '=', 'car_booking.booking_id')
            ->whereNull('bookings.deleted_at')
            ->where('car_id', $carId)
            ->when($excludeBookingId, function ($query) use ($excludeBookingId) {
                $query->where('bookings.id', '!=', $excludeBookingId);
            })
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('rental_start', [$start, $end])
                    ->orWhereBetween('rental_end', [$start, $end])
                    ->orWhere(function ($q) use ($start, $end) {
                        $q->where('rental_start', '<=', $start)
                            ->where('rental_end', '>=', $end);
                    });
            })
            ->exists();
    }

    /**
     * Same as hasConflict(), but only considers bookings that are already
     * approved. Used by Booking::approve() (TD-04): two competing *pending*
     * bookings for the same car/dates should be allowed to coexist — the
     * whole point of the re-check is to stop a second one being approved
     * for a car that's already been committed to someone else. Using the
     * status-agnostic hasConflict() here instead would make every pending
     * booking block every other pending booking's approval, including the
     * very first one, which defeats the fix.
     */
    public function hasApprovedConflict(int $carId, string $start, string $end, ?int $excludeBookingId = null): bool
    {
        return DB::table('car_booking')
            ->join('bookings', 'bookings.id', '=', 'car_booking.booking_id')
            ->whereNull('bookings.deleted_at')
            ->where('bookings.status', 'approved')
            ->where('car_id', $carId)
            ->when($excludeBookingId, function ($query) use ($excludeBookingId) {
                $query->where('bookings.id', '!=', $excludeBookingId);
            })
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('rental_start', [$start, $end])
                    ->orWhereBetween('rental_end', [$start, $end])
                    ->orWhere(function ($q) use ($start, $end) {
                        $q->where('rental_start', '<=', $start)
                            ->where('rental_end', '>=', $end);
                    });
            })
            ->exists();
    }

    /**
     * IDs of cars that are NOT available for [$start, $end] — used by
     * getAvailableCars() to filter a listing in one query instead of one
     * exists() query per candidate car.
     */
    public function unavailableCarIds(string $start, string $end, ?int $excludeBookingId = null): array
    {
        return DB::table('car_booking')
            ->join('bookings', 'bookings.id', '=', 'car_booking.booking_id')
            ->whereNull('bookings.deleted_at')
            ->when($excludeBookingId, function ($query) use ($excludeBookingId) {
                $query->where('bookings.id', '!=', $excludeBookingId);
            })
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('rental_start', [$start, $end])
                    ->orWhereBetween('rental_end', [$start, $end])
                    ->orWhere(function ($q) use ($start, $end) {
                        $q->where('rental_start', '<=', $start)
                            ->where('rental_end', '>=', $end);
                    });
            })
            ->pluck('car_id')
            ->unique()
            ->values()
            ->all();
    }
}
