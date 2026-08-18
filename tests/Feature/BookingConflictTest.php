<?php

/**
 * NOTE: this is a Pest test (uses it()/expect()), not PHPUnit. It won't run
 * yet — install Pest first:
 *
 *   composer require pestphp/pest pestphp/pest-plugin-laravel --dev --with-all-dependencies
 *   php artisan pest:install
 *   php artisan test
 */

use App\Models\Booking;
use App\Models\Car;
use App\Models\User;
use App\Services\BookingAvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('reports no conflict when the car has no overlapping bookings', function () {
    $car = Car::factory()->create();
    $service = app(BookingAvailabilityService::class);

    expect($service->hasConflict($car->id, '2026-09-01', '2026-09-05'))->toBeFalse();
});

it('reports a conflict when dates overlap an existing non-cancelled booking', function () {
    $car = Car::factory()->create();
    $booking = Booking::factory()->create(['status' => 'approved']);
    $booking->cars()->attach($car->id, [
        'rental_start' => '2026-09-01',
        'rental_end' => '2026-09-05',
        'quantity' => 1,
        'price' => 250,
    ]);

    $service = app(BookingAvailabilityService::class);

    expect($service->hasConflict($car->id, '2026-09-03', '2026-09-07'))->toBeTrue();
});

it('excludes the booking being edited from its own conflict check', function () {
    $car = Car::factory()->create();
    $booking = Booking::factory()->create(['status' => 'approved']);
    $booking->cars()->attach($car->id, [
        'rental_start' => '2026-09-01',
        'rental_end' => '2026-09-05',
        'quantity' => 1,
        'price' => 250,
    ]);

    $service = app(BookingAvailabilityService::class);

    expect($service->hasConflict($car->id, '2026-09-01', '2026-09-05', $booking->id))->toBeFalse();
});

it('does not double-approve two overlapping pending bookings for the same car (TD-04 regression)', function () {
    $car = Car::factory()->create();
    $admin = User::factory()->create(['userLevel' => 5]);

    $first = Booking::factory()->create([
        'status' => 'pending',
        'start_date' => now()->addDays(3)->toDateString(),
        'end_date' => now()->addDays(6)->toDateString(),
    ]);
    $first->cars()->attach($car->id, [
        'rental_start' => $first->start_date, 'rental_end' => $first->end_date,
        'quantity' => 1, 'price' => 250,
    ]);

    $second = Booking::factory()->create([
        'status' => 'pending',
        'start_date' => now()->addDays(4)->toDateString(),
        'end_date' => now()->addDays(7)->toDateString(),
    ]);
    $second->cars()->attach($car->id, [
        'rental_start' => $second->start_date, 'rental_end' => $second->end_date,
        'quantity' => 1, 'price' => 250,
    ]);

    expect($first->approve($admin))->toBeTrue();

    // Before the TD-04 fix, this second approve() would also have
    // succeeded, silently double-booking the car.
    expect($second->fresh()->approve($admin))->toBeFalse();
    expect($second->fresh()->status)->toBe('pending');
});
