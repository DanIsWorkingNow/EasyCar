<?php

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Car;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('computes fleet utilization correctly against a known fixture', function () {
    $branch = Branch::factory()->create();
    // 2 cars, 10-day window, 5 booked car-days total -> 5 / (2*10) = 0.25
    $car1 = Car::factory()->create(['branch_id' => $branch->id]);
    Car::factory()->create(['branch_id' => $branch->id]); // second car, unused

    $from = now()->startOfDay();
    $to = $from->copy()->addDays(9); // 10-day window inclusive

    $booking = Booking::factory()->approved()->create([
        'start_date' => $from->toDateString(),
        'end_date' => $from->copy()->addDays(4)->toDateString(), // 5-day rental
    ]);

    $booking->cars()->attach($car1->id, [
        'rental_start' => $from->toDateString(),
        'rental_end' => $from->copy()->addDays(4)->toDateString(), // 5 days
        'quantity' => 1,
        'price' => 250,
    ]);

    $utilization = app(DashboardService::class)->getUtilization($branch->id, $from, $to);

    expect($utilization)->toBe(0.25);
});

it('excludes pending and rejected bookings from utilization', function () {
    $branch = Branch::factory()->create();
    $car = Car::factory()->create(['branch_id' => $branch->id]);

    $from = now()->startOfDay();
    $to = $from->copy()->addDays(9);

    $pending = Booking::factory()->create([
        'status' => 'pending',
        'start_date' => $from->toDateString(),
        'end_date' => $from->copy()->addDays(4)->toDateString(),
    ]);
    $pending->cars()->attach($car->id, [
        'rental_start' => $from->toDateString(),
        'rental_end' => $from->copy()->addDays(4)->toDateString(),
        'quantity' => 1,
        'price' => 250,
    ]);

    $utilization = app(DashboardService::class)->getUtilization($branch->id, $from, $to);

    expect($utilization)->toBe(0.0);
});

it('scopes KPIs to a single branch and excludes other branches', function () {
    $branchA = Branch::factory()->create();
    $branchB = Branch::factory()->create();

    $carA = Car::factory()->create(['branch_id' => $branchA->id]);
    $carB = Car::factory()->create(['branch_id' => $branchB->id]);

    // total_price must be set explicitly here to match the pivot price below —
    // the factory defaults it to 0, and nothing auto-syncs it from the pivot
    // (in the real app, BookingController::store() computes and saves it at
    // creation time). revenue_period sums bookings.total_price directly, not
    // the pivot price, so leaving this out silently makes the assertion below
    // compare against 0 regardless of what's attached.
    Booking::factory()->approved()->create(['total_price' => 100])->cars()->attach($carA->id, [
        'rental_start' => now(), 'rental_end' => now()->addDay(), 'quantity' => 1, 'price' => 100,
    ]);
    Booking::factory()->approved()->create(['total_price' => 999])->cars()->attach($carB->id, [
        'rental_start' => now(), 'rental_end' => now()->addDay(), 'quantity' => 1, 'price' => 999,
    ]);

    $from = now()->subDay();
    $to = now()->addDay();

    $kpisA = app(DashboardService::class)->getKpis($branchA->id, $from, $to);

    expect($kpisA['revenue_period'])->toBe(100.0);
});
