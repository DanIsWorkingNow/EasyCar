<?php

use App\Livewire\Booking\CarAvailabilityPicker;
use App\Models\Booking;
use App\Models\Car;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('excludes a car from availableCount once dates conflict with an existing booking', function () {
    $car = Car::factory()->create();
    $available = Car::factory()->create();

    $existing = Booking::factory()->approved()->create([
        'start_date' => now()->addDays(3)->toDateString(),
        'end_date' => now()->addDays(6)->toDateString(),
    ]);
    $existing->cars()->attach($car->id, [
        'rental_start' => $existing->start_date, 'rental_end' => $existing->end_date,
        'quantity' => 1, 'price' => 100,
    ]);

    $this->actingAs(User::factory()->create());

    Livewire::test(CarAvailabilityPicker::class)
        ->set('startDate', now()->addDays(4)->toDateString())
        ->set('endDate', now()->addDays(7)->toDateString())
        ->assertViewHas('availableCount', 1) // only $available, not $car
        ->assertViewHas('unavailableIds', fn ($ids) => in_array($car->id, $ids));
});

it('refuses to select a third car', function () {
    [$a, $b, $c] = Car::factory()->count(3)->create();
    $this->actingAs(User::factory()->create());

    Livewire::test(CarAvailabilityPicker::class)
        ->call('toggleCar', $a->id)
        ->call('toggleCar', $b->id)
        ->call('toggleCar', $c->id)
        ->assertHasErrors('selectedCarIds');
});

it('creates a booking end-to-end and redirects to bookings.index', function () {
    $car = Car::factory()->create();
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(CarAvailabilityPicker::class)
        ->set('startDate', now()->addDays(3)->toDateString())
        ->set('endDate', now()->addDays(5)->toDateString())
        ->call('toggleCar', $car->id)
        ->call('createBooking')
        ->assertRedirect(route('bookings.index'));

    expect(Booking::where('user_id', $user->id)->exists())->toBeTrue();
});

it('rejects submission with no cars selected', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(CarAvailabilityPicker::class)
        ->set('startDate', now()->addDays(3)->toDateString())
        ->set('endDate', now()->addDays(5)->toDateString())
        ->call('createBooking')
        ->assertHasErrors('selectedCarIds');
});
