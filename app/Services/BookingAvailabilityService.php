<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Single source of truth for "is this car free for these dates" (TD-14).
 *
 * FIX (revamp): rejected and cancelled bookings no longer hold a car. The
 * previous query only excluded soft-deleted rows, so a rejected booking
 * blocked its car for those dates forever and customers saw it as
 * unavailable. Pending bookings still hold the car (they're reservations
 * awaiting approval/payment).
 */
class BookingAvailabilityService
{
    /**
     * Whether $carId has any active booking overlapping [$start, $end].
     * Pass $excludeBookingId when checking a booking being edited.
     */
    public function hasConflict(int $carId, string $start, string $end, ?int $excludeBookingId = null): bool
    {
        return $this->overlapping($start, $end, $excludeBookingId)
            ->where('car_booking.car_id', $carId)
            ->exists();
    }

    /**
     * Same as hasConflict(), but only considers bookings that are already
     * approved. Used by Booking::approve() (TD-04): competing *pending*
     * bookings may coexist; only an approved one blocks another approval.
     */
    public function hasApprovedConflict(int $carId, string $start, string $end, ?int $excludeBookingId = null): bool
    {
        return $this->overlapping($start, $end, $excludeBookingId)
            ->where('bookings.status', 'approved')
            ->where('car_booking.car_id', $carId)
            ->exists();
    }

    /**
     * IDs of cars NOT available for [$start, $end] — one query for a whole
     * listing instead of one exists() per car.
     *
     * @return int[]
     */
    public function unavailableCarIds(string $start, string $end, ?int $excludeBookingId = null): array
    {
        return $this->overlapping($start, $end, $excludeBookingId)
            ->pluck('car_booking.car_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Upcoming booked ranges for one car — feeds the availability strip on
     * the car detail page.
     *
     * @return array<int, array{start: string, end: string}>
     */
    public function bookedRanges(int $carId, int $days = 60): array
    {
        $from = now()->toDateString();
        $to = now()->addDays($days)->toDateString();

        return $this->overlapping($from, $to)
            ->where('car_booking.car_id', $carId)
            ->orderBy('car_booking.rental_start')
            ->get(['car_booking.rental_start', 'car_booking.rental_end'])
            ->map(fn ($row) => ['start' => (string) $row->rental_start, 'end' => (string) $row->rental_end])
            ->all();
    }

    private function overlapping(string $start, string $end, ?int $excludeBookingId = null): Builder
    {
        return DB::table('car_booking')
            ->join('bookings', 'bookings.id', '=', 'car_booking.booking_id')
            ->whereNull('bookings.deleted_at')
            ->whereNotIn('bookings.status', Booking::RELEASED_STATUSES)
            ->when($excludeBookingId, fn ($q) => $q->where('bookings.id', '!=', $excludeBookingId))
            // Two inclusive ranges overlap iff each starts on/before the other ends.
            ->whereDate('car_booking.rental_start', '<=', $end)
            ->whereDate('car_booking.rental_end', '>=', $start);
    }
}
