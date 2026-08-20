<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Car;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Before this kit, the create-a-booking logic (conflict check, DB
 * transaction, per-car price calculation, total_price update) existed
 * twice: BookingController::store() (web) and Api\V1\BookingController::
 * store() (API kit). This service is the one place it now lives; the new
 * CarAvailabilityPicker Livewire component uses it directly, and both
 * controllers can be updated to call it too rather than keep their own
 * copies.
 */
class BookingCreationService
{
    public function __construct(private BookingAvailabilityService $availability)
    {
    }

    /**
     * @param  int[]  $carIds
     *
     * @throws ValidationException if any selected car conflicts with an
     *         existing booking for the given date range.
     */
    public function create(User $user, array $carIds, string $startDate, string $endDate): Booking
    {
        foreach ($carIds as $carId) {
            if ($this->availability->hasConflict($carId, $startDate, $endDate)) {
                throw ValidationException::withMessages([
                    'cars' => ['One or more selected cars are not available for these dates.'],
                ]);
            }
        }

        // FIX: Carbon 3's diffInDays() can return a float with precision
        // noise (e.g. 3.0000000001 instead of clean 3) — cast explicitly so
        // total_days is a real int, not a float that happens to look like one.
        $totalDays = (int) Carbon::parse($startDate)->diffInDays($endDate) + 1;

        return DB::transaction(function () use ($user, $carIds, $startDate, $endDate, $totalDays) {
            $booking = Booking::create([
                'user_id' => $user->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total_days' => $totalDays,
                'status' => 'pending',
                'total_price' => 0,
            ]);

            $totalPrice = 0;

            foreach ($carIds as $carId) {
                $car = Car::findOrFail($carId);
                $price = $car->price_per_day * $totalDays;
                $totalPrice += $price;

                $booking->cars()->attach($carId, [
                    'rental_start' => $startDate,
                    'rental_end' => $endDate,
                    'quantity' => 1,
                    'price' => $price,
                ]);
            }

            $booking->update(['total_price' => $totalPrice]);

            return $booking;
        });
    }
}
