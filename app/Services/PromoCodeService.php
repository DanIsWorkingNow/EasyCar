<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\PromoCode;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Validates a promo code for a customer and basket. Redemptions are counted
 * from live bookings (cancelled/rejected ones don't count), so there is no
 * counter to drift. Call inside the booking transaction with $lock = true
 * so two customers can't both take the last redemption.
 */
class PromoCodeService
{
    /**
     * @throws ValidationException
     */
    public function evaluate(string $code, User $user, float $subtotal, bool $lock = false): PromoCode
    {
        $code = strtoupper(trim($code));

        $promo = PromoCode::where('code', $code)->when($lock, fn ($q) => $q->lockForUpdate())->first();

        $error = match (true) {
            ! $promo, ! $promo->is_active => 'This promo code isn\'t valid.',
            $promo->starts_at && $promo->starts_at->isFuture() => 'This promo code isn\'t active yet.',
            $promo->ends_at && $promo->ends_at->isPast() => 'This promo code has expired.',
            $promo->min_spend && $subtotal < $promo->min_spend => 'Spend at least RM '.number_format($promo->min_spend, 2).' to use this code.',
            $promo->max_redemptions !== null && $promo->redemptionCount() >= $promo->max_redemptions => 'This promo code has been fully redeemed.',
            $promo->per_user_limit > 0 && $promo->redemptionCount($user->id) >= $promo->per_user_limit => 'You\'ve already used this promo code.',
            $promo->first_booking_only && $this->hasBooked($user) => 'This code is for your first booking only.',
            default => null,
        };

        if ($error !== null) {
            throw ValidationException::withMessages(['promo_code' => [$error]]);
        }

        return $promo;
    }

    private function hasBooked(User $user): bool
    {
        return Booking::where('user_id', $user->id)->whereNotIn('status', Booking::RELEASED_STATUSES)->exists();
    }
}
