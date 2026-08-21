<?php

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Car;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('lets a customer create a booking and only see their own bookings', function () {
    $customer = User::factory()->create(['userLevel' => 0]);
    $customer->assignRole('customer');
    $car = Car::factory()->create();

    $token = $customer->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")->postJson('/api/v1/bookings', [
        'start_date' => now()->addDays(3)->toDateString(),
        'end_date' => now()->addDays(6)->toDateString(),
        'cars' => [$car->id],
    ]);

    $response->assertCreated()->assertJsonPath('data.status', 'pending');

    $otherCustomer = User::factory()->create();
    $otherCustomer->assignRole('customer');
    $otherToken = $otherCustomer->createToken('test')->plainTextToken;

    // See the comment in AuthTest's logout test — Sanctum's RequestGuard
    // caches the resolved user across sequential calls within one test
    // method, so without this the request below would still authenticate
    // as $customer instead of $otherCustomer (verified against the real
    // running app that per-customer isolation genuinely works).
    $this->app['auth']->forgetGuards();

    $list = $this->withHeader('Authorization', "Bearer {$otherToken}")->getJson('/api/v1/bookings');
    $list->assertOk()->assertJsonCount(0, 'data');
});

it('rejects a booking that conflicts with an existing approved booking', function () {
    $customer = User::factory()->create();
    $customer->assignRole('customer');
    $car = Car::factory()->create();

    $existing = Booking::factory()->approved()->create([
        'start_date' => now()->addDays(3)->toDateString(),
        'end_date' => now()->addDays(6)->toDateString(),
    ]);
    $existing->cars()->attach($car->id, [
        'rental_start' => $existing->start_date, 'rental_end' => $existing->end_date,
        'quantity' => 1, 'price' => 100,
    ]);

    $token = $customer->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")->postJson('/api/v1/bookings', [
        'start_date' => now()->addDays(4)->toDateString(),
        'end_date' => now()->addDays(7)->toDateString(),
        'cars' => [$car->id],
    ]);

    $response->assertStatus(422)->assertJsonValidationErrors('cars');
});

it('scopes staff bookings to their own branch and 403s outside it', function () {
    $branchA = Branch::factory()->create();
    $branchB = Branch::factory()->create();

    $staff = User::factory()->create(['userLevel' => 1, 'branch_id' => $branchA->id]);
    $staff->assignRole('staff');

    $carB = Car::factory()->create(['branch_id' => $branchB->id]);
    $booking = Booking::factory()->create();
    $booking->cars()->attach($carB->id, [
        'rental_start' => $booking->start_date, 'rental_end' => $booking->end_date,
        'quantity' => 1, 'price' => 100,
    ]);

    $token = $staff->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/v1/bookings/{$booking->id}")
        ->assertStatus(403);
});

it('lets staff approve a pending booking in their own branch', function () {
    $branch = Branch::factory()->create();
    $staff = User::factory()->create(['userLevel' => 1, 'branch_id' => $branch->id]);
    $staff->assignRole('staff');

    $car = Car::factory()->create(['branch_id' => $branch->id]);
    $booking = Booking::factory()->create([
        'status' => 'pending',
        'start_date' => now()->addDays(3)->toDateString(),
        'end_date' => now()->addDays(6)->toDateString(),
    ]);
    $booking->cars()->attach($car->id, [
        'rental_start' => $booking->start_date, 'rental_end' => $booking->end_date,
        'quantity' => 1, 'price' => 100,
    ]);

    $token = $staff->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->patchJson("/api/v1/bookings/{$booking->id}/approve")
        ->assertOk()
        ->assertJsonPath('data.status', 'approved');
});

it('routes GET /bookings/export to the export endpoint, not the {booking} wildcard (Rate Limit Kit route-order regression)', function () {
    // The Rate Limit Kit's own routes/api.php registers /bookings/export
    // AFTER /bookings/{booking} — Laravel matches routes in registration
    // order, so without this app's fix, this request would resolve to
    // BookingController::show() with booking="export" (a 404 from failed
    // route-model binding) instead of BookingController::export() (a CSV
    // stream). If this test ever fails with a 404, check routes/api.php's
    // ordering first, not the controller.
    $admin = User::factory()->create(['userLevel' => 5]);
    $admin->assignRole('admin');
    $token = $admin->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->get('/api/v1/bookings/export');

    $response->assertOk();
    expect($response->headers->get('Content-Disposition'))->toContain('bookings_export_');
});
