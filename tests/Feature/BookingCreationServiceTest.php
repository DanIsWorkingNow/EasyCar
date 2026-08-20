<?php

use App\Models\Booking;
use App\Models\Car;
use App\Models\User;
use App\Services\BookingCreationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('creates a pending booking with the correct total_price across two cars', function () {
    $user = User::factory()->create();
    $carA = Car::factory()->create(['price_per_day' => 100]);
    $carB = Car::factory()->create(['price_per_day' => 150]);

    $start = now()->addDays(3)->toDateString();
    $end = now()->addDays(5)->toDateString(); // 3 days inclusive

    $booking = app(BookingCreationService::class)->create($user, [$carA->id, $carB->id], $start, $end);

    expect($booking->status)->toBe('pending');
    expect($booking->total_days)->toBe(3);
    expect($booking->total_price)->toEqual(750.0); // (100*3) + (150*3)
    expect($booking->cars)->toHaveCount(2);
});

it('throws a validation exception when a selected car conflicts, and creates nothing', function () {
    $user = User::factory()->create();
    $car = Car::factory()->create();

    $existing = Booking::factory()->approved()->create([
        'start_date' => now()->addDays(3)->toDateString(),
        'end_date' => now()->addDays(6)->toDateString(),
    ]);
    $existing->cars()->attach($car->id, [
        'rental_start' => $existing->start_date, 'rental_end' => $existing->end_date,
        'quantity' => 1, 'price' => 100,
    ]);

    $countBefore = Booking::count();

    expect(fn () => app(BookingCreationService::class)->create(
        $user, [$car->id], now()->addDays(4)->toDateString(), now()->addDays(7)->toDateString()
    ))->toThrow(ValidationException::class);

    expect(Booking::count())->toBe($countBefore); // nothing partially created
});
