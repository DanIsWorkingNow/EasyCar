<?php

use App\Models\Booking;
use App\Models\Car;
use App\Models\User;
use App\Services\BookingAvailabilityService;
use App\Services\BookingCreationService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function attachCar(Booking $booking, Car $car): Booking
{
    $booking->cars()->attach($car->id, [
        'rental_start' => $booking->start_date, 'rental_end' => $booking->end_date,
        'quantity' => 1, 'price' => 100,
    ]);

    return $booking;
}

it('frees the car once a booking is rejected or cancelled', function () {
    $car = Car::factory()->create();
    $range = [now()->addDays(5)->toDateString(), now()->addDays(7)->toDateString()];
    $service = app(BookingAvailabilityService::class);

    $rejected = attachCar(Booking::factory()->create(['status' => 'rejected', 'start_date' => $range[0], 'end_date' => $range[1]]), $car);
    expect($service->hasConflict($car->id, ...$range))->toBeFalse();

    $rejected->update(['status' => 'cancelled']);
    expect($service->hasConflict($car->id, ...$range))->toBeFalse();

    $rejected->update(['status' => 'pending']);
    expect($service->hasConflict($car->id, ...$range))->toBeTrue();
});

it('re-prices a booking when the customer changes dates (old update kept the original price)', function () {
    $user = User::factory()->create();
    $car = Car::factory()->create(['price_per_day' => 100]);
    $booking = app(BookingCreationService::class)->create($user, [$car->id], now()->addDays(3)->toDateString(), now()->addDays(4)->toDateString());
    expect($booking->total_price)->toEqual(200.0);

    $this->actingAs($user)->put(route('bookings.update', $booking), [
        'start_date' => now()->addDays(3)->toDateString(),
        'end_date' => now()->addDays(7)->toDateString(),
        'cars' => [$car->id],
    ])->assertRedirect(route('bookings.show', $booking));

    $booking->refresh();
    expect($booking->total_days)->toBe(5)
        ->and($booking->total_price)->toEqual(500.0)
        ->and((float) $booking->cars->first()->pivot->price)->toEqual(500.0);
});

it('enforces the two-car limit across a customer\'s overlapping bookings in every flow', function () {
    $user = User::factory()->create();
    [$a, $b, $c] = Car::factory()->count(3)->create();
    $start = now()->addDays(3)->toDateString();
    $end = now()->addDays(5)->toDateString();

    app(BookingCreationService::class)->create($user, [$a->id, $b->id], $start, $end);

    expect(fn () => app(BookingCreationService::class)->create($user, [$c->id], $start, $end))
        ->toThrow(ValidationException::class);
});

it('lets the customer cancel instead of silently deleting the booking', function () {
    $user = User::factory()->create();
    $booking = attachCar(Booking::factory()->create(['user_id' => $user->id]), Car::factory()->create());

    $this->actingAs($user)->delete(route('bookings.destroy', $booking))->assertRedirect(route('bookings.index'));

    expect($booking->fresh()->status)->toBe('cancelled')
        ->and($booking->fresh()->trashed())->toBeFalse();
});

it('forbids other customers from viewing, editing or cancelling a booking', function () {
    $booking = attachCar(Booking::factory()->create(), Car::factory()->create());
    $other = User::factory()->create();

    $this->actingAs($other)->get(route('bookings.show', $booking))->assertForbidden();
    $this->actingAs($other)->put(route('bookings.update', $booking), [])->assertForbidden();
    $this->actingAs($other)->delete(route('bookings.destroy', $booking))->assertForbidden();
});

it('allows approving a booking made with exactly the minimum 2 days notice', function () {
    $admin = User::factory()->create();
    $booking = attachCar(Booking::factory()->create([
        'start_date' => now()->addDays(2)->toDateString(),
        'end_date' => now()->addDays(3)->toDateString(),
    ]), Car::factory()->create());

    expect($booking->canBeApproved())->toBeTrue()
        ->and($booking->approve($admin))->toBeTrue();
});

it('gives every booking a unique customer-facing reference', function () {
    $refs = Booking::factory()->count(20)->create()->pluck('reference');

    expect($refs->unique())->toHaveCount(20)
        ->and($refs->first())->toMatch('/^EC-[2-9A-HJ-NP-Z]{8}$/');
});

it('reaches the admin CSV export (was swallowed by /admin/bookings/{booking}) and neutralises formula injection', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $admin = User::factory()->create(['userLevel' => 5]);
    $admin->assignRole('admin');
    $evil = User::factory()->create(['name' => '=HYPERLINK("http://x")']);
    attachCar(Booking::factory()->create(['user_id' => $evil->id]), Car::factory()->create());

    $response = $this->actingAs($admin)->get('/admin/bookings/export');

    $response->assertOk();
    expect($response->headers->get('Content-Disposition'))->toContain('bookings_export_')
        ->and($response->streamedContent())->toContain("'=HYPERLINK");
});

it('reaches /bookings/available-cars (was swallowed by /bookings/{booking})', function () {
    Car::factory()->create();

    $this->actingAs(User::factory()->create())
        ->getJson('/bookings/available-cars?start_date='.now()->addDays(3)->toDateString().'&end_date='.now()->addDays(5)->toDateString())
        ->assertOk()
        ->assertJsonPath('count', 1);
});

it('assigns the customer role on self-registration', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->post('/register', [
        'name' => 'New Customer', 'email' => 'new@example.com',
        'password' => 'password123', 'password_confirmation' => 'password123',
    ])->assertRedirect();

    expect(User::where('email', 'new@example.com')->first()->hasRole('customer'))->toBeTrue();
});

it('re-dates the pivot rows when a booking is updated via the API', function () {
    $user = User::factory()->create();
    $car = Car::factory()->create(['price_per_day' => 50]);
    $booking = app(BookingCreationService::class)->create($user, [$car->id], now()->addDays(3)->toDateString(), now()->addDays(4)->toDateString());
    $token = $user->createToken('t')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")->putJson("/api/v1/bookings/{$booking->id}", [
        'start_date' => now()->addDays(10)->toDateString(),
        'end_date' => now()->addDays(12)->toDateString(),
    ])->assertOk()->assertJsonPath('data.total_price', 150);

    // The old window is free again; the new one is held.
    $service = app(BookingAvailabilityService::class);
    expect($service->hasConflict($car->id, now()->addDays(3)->toDateString(), now()->addDays(4)->toDateString()))->toBeFalse()
        ->and($service->hasConflict($car->id, now()->addDays(11)->toDateString(), now()->addDays(11)->toDateString()))->toBeTrue();
});
