<?php

/*
|--------------------------------------------------------------------------
| Rental operations policy
|--------------------------------------------------------------------------
|
| Drives the pickup/return handover, the automatic extra charges
| (App\Services\Rentals\ChargeCalculator) and the customer-facing policy
| text. Bookings are date-based; these times define when on those dates a
| car is handed over and due back.
|
*/

return [

    // Local times (app timezone) on the start/end dates.
    'pickup_time' => env('RENTAL_PICKUP_TIME', '10:00'),
    'return_time' => env('RENTAL_RETURN_TIME', '18:00'),

    // Returns within this many minutes of the due time are not late.
    'late_grace_minutes' => (int) env('RENTAL_LATE_GRACE_MINUTES', 60),

    // Each started 24 hours late is charged at the car's daily rate × this.
    'late_fee_multiplier' => (float) env('RENTAL_LATE_FEE_MULTIPLIER', 1.0),

    // Fuel is measured in eighths of a tank; a car returned with less fuel
    // than at pickup is charged per missing eighth.
    'fuel_charge_per_eighth' => (float) env('RENTAL_FUEL_CHARGE_PER_EIGHTH', 15),

    // Kilometres included per rental day. Null/empty = unlimited mileage.
    'daily_km_allowance' => env('RENTAL_DAILY_KM_ALLOWANCE') ? (int) env('RENTAL_DAILY_KM_ALLOWANCE') : null,
    'excess_km_rate' => (float) env('RENTAL_EXCESS_KM_RATE', 0.5),

    // Handover photos (stored on the private disk, never publicly served).
    'photos' => [
        'max_count' => 12,
        'max_kb' => 8192,
    ],

    // Condition checklist recorded at every handover.
    'checklist' => [
        'exterior_clean' => 'Exterior clean',
        'interior_clean' => 'Interior clean',
        'lights' => 'Lights & indicators working',
        'tyres' => 'Tyres & spare in good condition',
        'aircond' => 'Air-conditioning working',
        'documents' => 'Road tax & insurance documents in car',
        'accessories' => 'Jack, warning triangle & tools present',
    ],
];
