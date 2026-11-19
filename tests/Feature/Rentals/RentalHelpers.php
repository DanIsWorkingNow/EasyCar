<?php

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Car;
use App\Models\User;
use Illuminate\Support\Carbon;

/** A 1×1 PNG data URL, as the signature pad produces. */
function signaturePng(): string
{
    $img = imagecreatetruecolor(4, 4);
    ob_start();
    imagepng($img);

    return 'data:image/png;base64,'.base64_encode((string) ob_get_clean());
}

function branchStaff(Branch $branch): User
{
    $staff = User::factory()->create(['userLevel' => 1, 'branch_id' => $branch->id]);
    $staff->assignRole('staff');

    return $staff;
}

function adminUser(): User
{
    $admin = User::factory()->create(['userLevel' => 5]);
    $admin->assignRole('admin');

    return $admin;
}

/**
 * An approved booking 10–12 Dec 2026 (3 days) for one or more cars.
 *
 * @param  Car[]  $cars
 */
function approvedBooking(array $cars, array $attributes = []): Booking
{
    $booking = Booking::factory()->approved()->create(array_merge([
        'start_date' => '2026-12-10',
        'end_date' => '2026-12-12',
        'total_days' => 3,
        'total_price' => collect($cars)->sum(fn ($c) => $c->price_per_day * 3),
        'payment_status' => 'paid',
    ], $attributes));

    foreach ($cars as $car) {
        $booking->cars()->attach($car->id, [
            'rental_start' => $booking->start_date, 'rental_end' => $booking->end_date,
            'quantity' => 1, 'price' => $car->price_per_day * 3,
        ]);
    }

    return $booking->fresh();
}

function at(string $datetime): void
{
    test()->travelTo(Carbon::parse($datetime, config('app.timezone')));
}
