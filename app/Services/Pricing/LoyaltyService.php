<?php

namespace App\Services\Pricing;

use App\Models\Booking;
use App\Models\LoyaltyTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * EasyCar Rewards. A ledger, never a stored balance:
 *  - earn: when a booking completes, points_per_ringgit × what was paid;
 *  - redeem: points spent as a discount when booking;
 *  - reversal: redeemed points returned when a booking is cancelled or
 *    rejected (or when re-pricing lowers how many can be used);
 *  - adjustment: manual credit/debit by an admin.
 */
class LoyaltyService
{
    public function enabled(): bool
    {
        return (bool) config('pricing.loyalty.enabled');
    }

    public function pointValue(): float
    {
        return (float) config('pricing.loyalty.point_value');
    }

    public function valueOf(int $points): float
    {
        return round($points * $this->pointValue(), 2);
    }

    /**
     * How many points can be used on a basket, given the balance.
     */
    public function redeemablePoints(int $balance, float $payable): int
    {
        if (! $this->enabled() || $balance < (int) config('pricing.loyalty.min_redeem') || $payable <= 0) {
            return 0;
        }

        $maxValue = $payable * (float) config('pricing.loyalty.max_redeem_percent') / 100;

        return max(0, min($balance, (int) floor($maxValue / $this->pointValue() + 1e-9)));
    }

    /**
     * Spends points on a booking. Call inside the booking transaction.
     *
     * @return array{points: int, value: float}
     *
     * @throws ValidationException
     */
    public function redeem(User $user, Booking $booking, float $payable): array
    {
        // Serialise redemptions per customer so the balance can't be spent twice.
        User::whereKey($user->id)->lockForUpdate()->first();
        $points = $this->redeemablePoints($user->pointsBalance(), $payable);

        if ($points === 0) {
            throw ValidationException::withMessages(['points' => ['You need at least '.config('pricing.loyalty.min_redeem').' points to redeem.']]);
        }

        $user->loyaltyTransactions()->create([
            'booking_id' => $booking->id,
            'points' => -$points,
            'type' => LoyaltyTransaction::REDEEM,
            'description' => "Redeemed on booking {$booking->reference}",
        ]);

        return ['points' => $points, 'value' => $this->valueOf($points)];
    }

    /** Returns some redeemed points (re-pricing lowered the cap). */
    public function returnPoints(Booking $booking, int $points, string $why): void
    {
        if ($points <= 0) {
            return;
        }

        LoyaltyTransaction::create([
            'user_id' => $booking->user_id,
            'booking_id' => $booking->id,
            'points' => $points,
            'type' => LoyaltyTransaction::REVERSAL,
            'description' => $why,
        ]);
    }

    /** Booking cancelled or rejected: give back whatever is still redeemed on it. */
    public function reverseRedemption(Booking $booking): void
    {
        $net = (int) $booking->loyaltyTransactions()->whereIn('type', [LoyaltyTransaction::REDEEM, LoyaltyTransaction::REVERSAL])->sum('points');

        if ($net < 0) {
            $this->returnPoints($booking, -$net, "Points returned — booking {$booking->reference} {$booking->status}");
        }
    }

    /** Booking completed: award points once. */
    public function earnFor(Booking $booking): int
    {
        if (! $this->enabled() || $booking->loyaltyTransactions()->where('type', LoyaltyTransaction::EARN)->exists()) {
            return 0;
        }

        $points = (int) floor((float) $booking->total_price * (float) config('pricing.loyalty.points_per_ringgit'));
        if ($points <= 0) {
            return 0;
        }

        LoyaltyTransaction::create([
            'user_id' => $booking->user_id,
            'booking_id' => $booking->id,
            'points' => $points,
            'type' => LoyaltyTransaction::EARN,
            'description' => "Trip completed — booking {$booking->reference}",
        ]);

        return $points;
    }

    /** Manual credit (positive) or debit (negative) by an admin. */
    public function adjust(User $customer, int $points, string $reason, User $admin): LoyaltyTransaction
    {
        return DB::transaction(function () use ($customer, $points, $reason, $admin) {
            User::whereKey($customer->id)->lockForUpdate()->first();

            if ($points < 0 && $customer->pointsBalance() + $points < 0) {
                throw ValidationException::withMessages(['points' => ['That would take the balance below zero.']]);
            }

            return $customer->loyaltyTransactions()->create([
                'points' => $points,
                'type' => LoyaltyTransaction::ADJUSTMENT,
                'description' => $reason,
                'created_by' => $admin->id,
            ]);
        });
    }
}
