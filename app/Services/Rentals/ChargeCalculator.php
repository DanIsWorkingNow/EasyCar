<?php

namespace App\Services\Rentals;

use App\Enums\ChargeType;
use App\Models\Booking;
use App\Models\Car;
use Carbon\CarbonInterface;

/**
 * Works out the automatic extras for one returned car, from the booking,
 * the pickup record and what staff measured at return. Pure calculation —
 * nothing is persisted here, so the handover form can show a live quote
 * with exactly the numbers that will be charged.
 *
 * Policy (config/rentals.php):
 *  - late return: each started 24 h after due time + grace, at the car's
 *    daily rate × late_fee_multiplier;
 *  - fuel: each eighth of a tank below the pickup level;
 *  - mileage: kilometres over daily_km_allowance × days (when set).
 */
class ChargeCalculator
{
    /**
     * @return array<int, array{type: ChargeType, description: string, quantity: float, unit_amount: float, amount: float}>
     */
    public function forReturn(
        Booking $booking,
        Car $car,
        int $pickupOdometer,
        int $pickupFuel,
        CarbonInterface $returnedAt,
        int $returnOdometer,
        int $returnFuel,
    ): array {
        $lines = [];
        $rate = (float) $car->price_per_day;

        $lateDays = $this->lateDays($booking, $returnedAt);
        if ($lateDays > 0) {
            $unit = round($rate * (float) config('rentals.late_fee_multiplier', 1.0), 2);
            $lines[] = $this->line(
                ChargeType::LateReturn,
                "{$car->name}: returned {$lateDays} ".str('day')->plural($lateDays).' late (due '.$booking->returnDueAt()->format('d M, g:i A').')',
                $lateDays,
                $unit,
            );
        }

        $missingFuel = max(0, $pickupFuel - $returnFuel);
        if ($missingFuel > 0) {
            $lines[] = $this->line(
                ChargeType::Fuel,
                "{$car->name}: fuel {$missingFuel}/8 tank below pick-up level",
                $missingFuel,
                (float) config('rentals.fuel_charge_per_eighth', 15),
            );
        }

        $allowancePerDay = config('rentals.daily_km_allowance');
        if ($allowancePerDay) {
            $allowance = (int) $allowancePerDay * ($booking->total_days + $lateDays);
            $excess = max(0, ($returnOdometer - $pickupOdometer) - $allowance);
            if ($excess > 0) {
                $lines[] = $this->line(
                    ChargeType::Mileage,
                    "{$car->name}: {$excess} km over the {$allowance} km allowance",
                    $excess,
                    (float) config('rentals.excess_km_rate', 0.5),
                );
            }
        }

        return $lines;
    }

    /**
     * Started 24-hour blocks past the due time, after the grace period.
     */
    public function lateDays(Booking $booking, CarbonInterface $returnedAt): int
    {
        $due = $booking->returnDueAt();
        $grace = (int) config('rentals.late_grace_minutes', 60);

        if ($returnedAt->lessThanOrEqualTo($due->copy()->addMinutes($grace))) {
            return 0;
        }

        return (int) ceil($due->diffInMinutes($returnedAt) / 1440);
    }

    /**
     * @return array{type: ChargeType, description: string, quantity: float, unit_amount: float, amount: float}
     */
    private function line(ChargeType $type, string $description, float $quantity, float $unit): array
    {
        return [
            'type' => $type,
            'description' => $description,
            'quantity' => $quantity,
            'unit_amount' => round($unit, 2),
            'amount' => round($quantity * $unit, 2),
        ];
    }
}
