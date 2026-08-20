<?php

use App\Models\Booking;
use App\Models\Car;
use App\Models\User;
use App\Notifications\BookingStatusChanged;
use App\Notifications\RentalReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

it('sends BookingStatusChanged when a booking is approved', function () {
    Notification::fake();

    $car = Car::factory()->create();
    $admin = User::factory()->create();
    $booking = Booking::factory()->create([
        'start_date' => now()->addDays(3)->toDateString(),
        'end_date' => now()->addDays(6)->toDateString(),
    ]);
    $booking->cars()->attach($car->id, [
        'rental_start' => $booking->start_date, 'rental_end' => $booking->end_date,
        'quantity' => 1, 'price' => 250,
    ]);

    $booking->approve($admin);

    Notification::assertSentTo($booking->user, BookingStatusChanged::class);
});

it('sends BookingStatusChanged when a booking is rejected', function () {
    Notification::fake();

    $admin = User::factory()->create();
    $booking = Booking::factory()->create();

    $booking->reject($admin, 'Car under maintenance.');

    Notification::assertSentTo($booking->user, BookingStatusChanged::class);
});

it('does not send a notification when approve() fails validation', function () {
    Notification::fake();

    $admin = User::factory()->create();
    // starts tomorrow -> fails the 2-day lead time rule -> approve() returns false
    $booking = Booking::factory()->create(['start_date' => now()->addDay()->toDateString()]);

    $result = $booking->approve($admin);

    expect($result)->toBeFalse();
    Notification::assertNothingSent();
});

it('queues a pickup and a return reminder for bookings due tomorrow', function () {
    Notification::fake();

    $carA = Car::factory()->create();
    $carB = Car::factory()->create();

    $pickupTomorrow = Booking::factory()->approved()->create([
        'start_date' => now()->addDay()->toDateString(),
        'end_date' => now()->addDays(5)->toDateString(),
    ]);
    $pickupTomorrow->cars()->attach($carA->id, [
        'rental_start' => $pickupTomorrow->start_date, 'rental_end' => $pickupTomorrow->end_date,
        'quantity' => 1, 'price' => 100,
    ]);

    $returnTomorrow = Booking::factory()->approved()->create([
        'start_date' => now()->subDays(4)->toDateString(),
        'end_date' => now()->addDay()->toDateString(),
    ]);
    $returnTomorrow->cars()->attach($carB->id, [
        'rental_start' => $returnTomorrow->start_date, 'rental_end' => $returnTomorrow->end_date,
        'quantity' => 1, 'price' => 100,
    ]);

    Artisan::call('bookings:send-reminders');

    Notification::assertSentTo($pickupTomorrow->user, RentalReminder::class, fn ($n) => $n->type === 'pickup');
    Notification::assertSentTo($returnTomorrow->user, RentalReminder::class, fn ($n) => $n->type === 'return');
});
