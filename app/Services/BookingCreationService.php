<?php

namespace App\Services;

use App\Models\Addon;
use App\Models\Booking;
use App\Models\Car;
use App\Models\PromoCode;
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
 *    from the client;
 *  - paid add-ons (child seat, GPS…) and a promo code are priced here too:
 *    total_price = rental + addons_amount − discount_amount.
 */
class BookingCreationService
{
    public const MAX_CARS_PER_PERIOD = 2;

    public function __construct(
        private BookingAvailabilityService $availability,
        private PromoCodeService $promos,
    ) {}

    /**
     * @param  int[]  $carIds
     * @param  array<int|string, int|string>  $addons  addon id => quantity
     *
     * @throws ValidationException
     */
    public function create(User $user, array $carIds, string $startDate, string $endDate, ?string $acceptedFromIp = null, array $addons = [], ?string $promoCode = null): Booking
    {
        $carIds = $this->normaliseIds($carIds);
        $addons = $this->normaliseAddons($addons);
        $promoCode = filled($promoCode) ? (string) $promoCode : null;

        return DB::transaction(function () use ($user, $carIds, $startDate, $endDate, $acceptedFromIp, $addons, $promoCode) {
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
                // Rental agreement acceptance (Phase 3). Null when the
                // caller didn't collect consent (e.g. older API clients).
                'terms_version' => $acceptedFromIp !== null ? config('compliance.terms_version') : null,
                'terms_accepted_at' => $acceptedFromIp !== null ? now() : null,
                'terms_accepted_ip' => $acceptedFromIp,
            ]);

            $rental = $this->attachCars($booking, $carIds, $startDate, $endDate, $totalDays);
            $addonsAmount = $this->syncAddons($booking, $addons, $totalDays);

            $promo = $promoCode !== null ? $this->promos->evaluate($promoCode, $user, $rental + $addonsAmount, lock: true) : null;

            $this->applyTotals($booking, $rental, $addonsAmount, $promo);

            return $booking->load('cars', 'addons');
        });
    }

    /**
     * Re-dates / re-cars an existing pending booking and recomputes its
     * price. FIX: the old controller update path re-inserted pivot rows
     * without price/quantity and never recalculated total_price, so an
     * edited booking kept charging the original amount.
     *
     * Add-ons are kept (per-day ones re-priced for the new length) unless
     * $addons is given. A promo code already on the booking stays applied
     * and is recomputed for the new subtotal — its window and limits were
     * checked when it was redeemed.
     *
     * @param  int[]  $carIds
     * @param  array<int|string, int|string>|null  $addons
     *
     * @throws ValidationException
     */
    public function update(Booking $booking, array $carIds, string $startDate, string $endDate, ?array $addons = null): Booking
    {
        $carIds = $this->normaliseIds($carIds);
        $addons = $addons === null
            ? $booking->addons()->pluck('quantity', 'addon_id')->all()
            : $this->normaliseAddons($addons);

        return DB::transaction(function () use ($booking, $carIds, $startDate, $endDate, $addons) {
            Car::whereIn('id', $carIds)->lockForUpdate()->get();

            $this->assertAvailable($carIds, $startDate, $endDate, $booking->id);
            $this->assertWithinCustomerLimit($booking->user, $carIds, $startDate, $endDate, $booking->id);

            $totalDays = $this->totalDays($startDate, $endDate);

            $booking->cars()->detach();
            $rental = $this->attachCars($booking, $carIds, $startDate, $endDate, $totalDays);
            $addonsAmount = $this->syncAddons($booking, $addons, $totalDays);

            $booking->fill([
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total_days' => $totalDays,
            ]);
            $this->applyTotals($booking, $rental, $addonsAmount, $booking->promoCode);

            return $booking->load('cars', 'addons');
        });
    }

    /**
     * Price quote without persisting anything (used by the booking UI).
     * An invalid promo code doesn't throw: the reason comes back in
     * promo_error and no discount is applied.
     *
     * @param  int[]  $carIds
     * @param  array<int|string, int|string>  $addons
     * @return array{days: int, rental: float, addons_total: float, discount: float, total: float, promo: ?PromoCode, promo_error: ?string, lines: array<int, array{car_id: int, name: string, price: float}>, addon_lines: array<int, array{addon_id: int, name: string, quantity: int, price: float}>}
     */
    public function quote(array $carIds, string $startDate, string $endDate, array $addons = [], ?User $user = null, ?string $promoCode = null): array
    {
        $days = $this->totalDays($startDate, $endDate);
        $lines = Car::whereIn('id', $this->normaliseIds($carIds))->get()->map(fn (Car $car) => [
            'car_id' => $car->id,
            'name' => $car->name,
            'price' => round((float) $car->price_per_day * $days, 2),
        ])->values()->all();

        $addons = $this->normaliseAddons($addons);
        $addonLines = Addon::whereIn('id', array_keys($addons))->where('is_active', true)->orderBy('sort_order')->get()
            ->map(fn (Addon $addon) => [
                'addon_id' => $addon->id,
                'name' => $addon->name,
                'quantity' => $qty = min($addons[$addon->id], $addon->max_quantity),
                'price' => $addon->amountFor($qty, $days),
            ])->values()->all();

        $rental = round(array_sum(array_column($lines, 'price')), 2);
        $addonsTotal = round(array_sum(array_column($addonLines, 'price')), 2);

        $promo = null;
        $promoError = null;
        if (filled($promoCode) && $user && $lines !== []) {
            try {
                $promo = $this->promos->evaluate((string) $promoCode, $user, $rental + $addonsTotal);
            } catch (ValidationException $e) {
                $promoError = $e->validator->errors()->first('promo_code');
            }
        }

        $discount = $promo ? $promo->discountFor($rental + $addonsTotal) : 0.0;

        return [
            'days' => $days,
            'rental' => $rental,
            'addons_total' => $addonsTotal,
            'discount' => $discount,
            'total' => round($rental + $addonsTotal - $discount, 2),
            'promo' => $promo,
            'promo_error' => $promoError,
            'lines' => $lines,
            'addon_lines' => $addonLines,
        ];
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
     * Replaces the booking's add-ons, snapshotting name and price.
     *
     * @param  array<int, int>  $addons
     */
    private function syncAddons(Booking $booking, array $addons, int $totalDays): float
    {
        $booking->addons()->delete();

        if ($addons === []) {
            return 0.0;
        }

        $total = 0.0;
        $found = Addon::whereIn('id', array_keys($addons))->where('is_active', true)->get()->keyBy('id');

        foreach ($addons as $addonId => $quantity) {
            /** @var Addon|null $addon */
            $addon = $found->get($addonId);

            if (! $addon) {
                throw ValidationException::withMessages(['addons' => ['One of the selected extras is no longer available.']]);
            }
            if ($quantity > $addon->max_quantity) {
                throw ValidationException::withMessages(['addons' => ["You can add at most {$addon->max_quantity} × {$addon->name}."]]);
            }

            $amount = $addon->amountFor($quantity, $totalDays);
            $total += $amount;

            $booking->addons()->create([
                'addon_id' => $addon->id,
                'name' => $addon->name,
                'pricing' => $addon->pricing,
                'quantity' => $quantity,
                'unit_price' => $addon->price,
                'amount' => $amount,
            ]);
        }

        return round($total, 2);
    }

    private function applyTotals(Booking $booking, float $rental, float $addonsAmount, ?PromoCode $promo): void
    {
        $discount = $promo ? $promo->discountFor($rental + $addonsAmount) : 0.0;

        $booking->fill([
            'addons_amount' => $addonsAmount,
            'promo_code_id' => $promo?->id,
            'discount_amount' => $discount,
            'total_price' => round($rental + $addonsAmount - $discount, 2),
        ])->save();
    }

    /**
     * addon id => quantity, dropping zero/negative quantities.
     *
     * @param  array<int|string, int|string>  $addons
     * @return array<int, int>
     */
    private function normaliseAddons(array $addons): array
    {
        $clean = [];
        foreach ($addons as $id => $qty) {
            if ((int) $qty > 0 && (int) $id > 0) {
                $clean[(int) $id] = (int) $qty;
            }
        }

        return $clean;
    }

    /**
     * @return int[]
     */
    private function normaliseIds(array $carIds): array
    {
        return array_values(array_unique(array_map('intval', $carIds)));
    }
}
