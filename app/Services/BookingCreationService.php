<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Car;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The one place a booking is created or re-priced. Used by the Livewire
 * booking flow, the web BookingController and the REST API so the
 * business rules can't drift between them:
 *
 *  - every selected car must be free for the date range;
 *  - a customer may hold at most MAX_CARS_PER_PERIOD cars across all of
 *    their active bookings that overlap the range (previously only the
 *    legacy web form enforced this — the Livewire flow and API skipped it);
 *  - prices are computed server-side from price_per_day, never trusted
 *    from the client.
 */
class BookingCreationService
{
    public const MAX_CARS_PER_PERIOD = 2;

    public function __construct(private BookingAvailabilityService $availability) {}

    /**
     * @param  int[]  $carIds
     *
     * @throws ValidationException
     */
    public function create(User $user, array $carIds, string $startDate, string $endDate): Booking
    {
        $carIds = $this->normaliseIds($carIds);

        return DB::transaction(function () use ($user, $carIds, $startDate, $endDate) {
            // Serialise concurrent submissions for the same cars so two
            // customers can't both pass the availability check.
            Car::whereIn('id', $carIds)->lockForUpdate()->get();

            $this->assertAvailable($carIds, $startDate, $endDate);
            $this->assertWithinCustomerLimit($user, $carIds, $startDate, $endDate);

            $totalDays = $this->totalDays($startDate, $endDate);

            $booking = Booking::create([
                'user_id' => $user->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total_days' => $totalDays,
                'status' => 'pending',
                'total_price' => 0,
            ]);

            $booking->update(['total_price' => $this->attachCars($booking, $carIds, $startDate, $endDate, $totalDays)]);

            return $booking->load('cars');
        });
    }

    /**
     * Re-dates / re-cars an existing pending booking and recomputes its
     * price. FIX: the old controller update path re-inserted pivot rows
     * without price/quantity and never recalculated total_price, so an
     * edited booking kept charging the original amount.
     *
     * @param  int[]  $carIds
     *
     * @throws ValidationException
     */
    public function update(Booking $booking, array $carIds, string $startDate, string $endDate): Booking
    {
        $carIds = $this->normaliseIds($carIds);

        return DB::transaction(function () use ($booking, $carIds, $startDate, $endDate) {
            Car::whereIn('id', $carIds)->lockForUpdate()->get();

            $this->assertAvailable($carIds, $startDate, $endDate, $booking->id);
            $this->assertWithinCustomerLimit($booking->user, $carIds, $startDate, $endDate, $booking->id);

            $totalDays = $this->totalDays($startDate, $endDate);

            $booking->cars()->detach();
            $total = $this->attachCars($booking, $carIds, $startDate, $endDate, $totalDays);

            $booking->update([
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total_days' => $totalDays,
                'total_price' => $total,
            ]);

            return $booking->load('cars');
        });
    }

    /**
     * Price quote without persisting anything (used by the booking UI).
     *
     * @param  int[]  $carIds
     * @return array{days: int, total: float, lines: array<int, array{car_id: int, name: string, price: float}>}
     */
    public function quote(array $carIds, string $startDate, string $endDate): array
    {
        $days = $this->totalDays($startDate, $endDate);
        $lines = Car::whereIn('id', $this->normaliseIds($carIds))->get()->map(fn (Car $car) => [
            'car_id' => $car->id,
            'name' => $car->name,
            'price' => round((float) $car->price_per_day * $days, 2),
        ])->values()->all();

        return ['days' => $days, 'total' => round(array_sum(array_column($lines, 'price')), 2), 'lines' => $lines];
    }

    public function totalDays(string $startDate, string $endDate): int
    {
        // Carbon 3's diffInDays() can return a float with precision noise —
        // cast so total_days is a clean int. Inclusive of both ends.
        return (int) Carbon::parse($startDate)->startOfDay()->diffInDays(Carbon::parse($endDate)->startOfDay()) + 1;
    }

    private function assertAvailable(array $carIds, string $startDate, string $endDate, ?int $excludeBookingId = null): void
    {
        foreach ($carIds as $carId) {
            if ($this->availability->hasConflict($carId, $startDate, $endDate, $excludeBookingId)) {
                $car = Car::find($carId);

                throw ValidationException::withMessages([
                    'cars' => [$car
                        ? "The {$car->brand} {$car->model} is not available for these dates."
                        : 'One or more selected cars are not available for these dates.'],
                ]);
            }
        }
    }

    private function assertWithinCustomerLimit(User $user, array $carIds, string $startDate, string $endDate, ?int $excludeBookingId = null): void
    {
        $alreadyHeld = DB::table('car_booking')
            ->join('bookings', 'bookings.id', '=', 'car_booking.booking_id')
            ->where('bookings.user_id', $user->id)
            ->whereNull('bookings.deleted_at')
            ->whereNotIn('bookings.status', Booking::RELEASED_STATUSES)
            ->when($excludeBookingId, fn ($q) => $q->where('bookings.id', '!=', $excludeBookingId))
            ->whereDate('car_booking.rental_start', '<=', $endDate)
            ->whereDate('car_booking.rental_end', '>=', $startDate)
            ->count();

        if ($alreadyHeld + count($carIds) > self::MAX_CARS_PER_PERIOD) {
            throw ValidationException::withMessages([
                'cars' => ['You can have at most '.self::MAX_CARS_PER_PERIOD.' cars booked for any overlapping period.'],
            ]);
        }
    }

    private function attachCars(Booking $booking, array $carIds, string $startDate, string $endDate, int $totalDays): float
    {
        $total = 0.0;

        foreach (Car::whereIn('id', $carIds)->get() as $car) {
            $price = round((float) $car->price_per_day * $totalDays, 2);
            $total += $price;

            $booking->cars()->attach($car->id, [
                'rental_start' => $startDate,
                'rental_end' => $endDate,
                'quantity' => 1,
                'price' => $price,
            ]);
        }

        return round($total, 2);
    }

    /**
     * @return int[]
     */
    private function normaliseIds(array $carIds): array
    {
        return array_values(array_unique(array_map('intval', $carIds)));
    }
}
