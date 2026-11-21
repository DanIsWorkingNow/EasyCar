<?php

/*
|--------------------------------------------------------------------------
| Pricing & revenue policy (Phase 5)
|--------------------------------------------------------------------------
|
| Daily rates start from each car's price_per_day and are adjusted by the
| seasonal and weekend rules managed in Admin → Pricing. The settings here
| cover what isn't edited day to day.
|
*/

return [

    // Long-rental discounts on the car hire (not extras): minimum days =>
    // percent off. The highest tier the trip qualifies for applies.
    'duration_discounts' => array_filter([
        28 => (float) env('PRICING_MONTHLY_DISCOUNT', 20),
        7 => (float) env('PRICING_WEEKLY_DISCOUNT', 10),
    ]),

    // Refundable security deposit taken at the counter at pick-up (cash,
    // card pre-authorisation on the terminal, or transfer) and released or
    // applied to extra charges after the return.
    'deposit' => [
        'required' => (bool) env('PRICING_DEPOSIT_REQUIRED', true),
        'default' => (float) env('PRICING_DEPOSIT_DEFAULT', 200),
        // Per car type; a car's own deposit_amount overrides these.
        'by_type' => [
            'SUV' => 300,
            'MPV' => 300,
        ],
        'methods' => [
            'card_hold' => 'Card pre-authorisation',
            'cash' => 'Cash',
            'bank_transfer' => 'Bank transfer / DuitNow',
        ],
    ],

    'loyalty' => [
        'enabled' => (bool) env('LOYALTY_ENABLED', true),
        // Points earned per RM 1 paid for a completed booking.
        'points_per_ringgit' => (float) env('LOYALTY_POINTS_PER_RINGGIT', 1),
        // Value of one point when redeemed (RM). 0.05 → 100 points = RM 5.
        'point_value' => (float) env('LOYALTY_POINT_VALUE', 0.05),
        'min_redeem' => (int) env('LOYALTY_MIN_REDEEM', 100),
        // Points can cover at most this share of a booking.
        'max_redeem_percent' => (float) env('LOYALTY_MAX_REDEEM_PERCENT', 50),
    ],
];
