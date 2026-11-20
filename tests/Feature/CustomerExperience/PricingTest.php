<?php

require_once __DIR__.'/../Rentals/RentalHelpers.php';

use App\Livewire\Booking\CarAvailabilityPicker;
use App\Models\Addon;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Car;
use App\Models\PromoCode;
use App\Models\User;
use App\Services\BookingCreationService;
use App\Services\PaymentService;
use Database\Seeders\CustomerExperienceSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->branch = Branch::factory()->create();
    $this->car = Car::factory()->create(['branch_id' => $this->branch->id, 'price_per_day' => 100]);
    $this->user = User::factory()->create();
    $this->start = now()->addDays(5)->toDateString();
    $this->end = now()->addDays(7)->toDateString(); // 3 days → RM 300 rental

    $this->seat = Addon::create(['name' => 'Booster seat (test)', 'pricing' => Addon::PER_DAY, 'price' => 10, 'max_quantity' => 2]);
    $this->driver = Addon::create(['name' => 'Second driver (test)', 'pricing' => Addon::PER_BOOKING, 'price' => 15, 'max_quantity' => 1]);
});

function book(array $addons = [], ?string $promo = null, ?User $user = null, ?Car $car = null): Booking
{
    return app(BookingCreationService::class)->create(
        $user ?? test()->user, [($car ?? test()->car)->id], test()->start, test()->end, '127.0.0.1', $addons, $promo,
    );
}

it('prices per-day and per-trip add-ons and snapshots them', function () {
    $booking = book([$this->seat->id => 2, $this->driver->id => 1]);

    // 300 rental + 2 seats × RM10 × 3 days + RM15 once
    expect($booking->total_price)->toBe(375.0)
        ->and($booking->addons_amount)->toBe(75.0)
        ->and($booking->rentalAmount())->toBe(300.0)
        ->and($booking->addons)->toHaveCount(2);

    // Later price changes don't touch the booking.
    $this->seat->update(['price' => 99]);
    expect($booking->fresh()->addons->firstWhere('addon_id', $this->seat->id)->amount)->toBe(60.0);
});

it('rejects quantities above the limit and inactive add-ons', function () {
    expect(fn () => book([$this->seat->id => 3]))->toThrow(ValidationException::class);

    $this->driver->update(['is_active' => false]);
    expect(fn () => book([$this->driver->id => 1]))->toThrow(ValidationException::class)
        ->and(Booking::count())->toBe(0); // rolled back
});

it('applies percentage codes with a cap and fixed codes', function () {
    PromoCode::create(['code' => 'pct50', 'type' => 'percent', 'value' => 50, 'max_discount' => 100, 'per_user_limit' => 0]);
    $booking = book(promo: 'PCT50');
    expect($booking->discount_amount)->toBe(100.0)->and($booking->total_price)->toBe(200.0)
        ->and($booking->promoCode->code)->toBe('PCT50'); // stored upper-case

    PromoCode::create(['code' => 'FLAT20', 'type' => 'fixed', 'value' => 20, 'per_user_limit' => 0]);
    expect(book([$this->driver->id => 1], 'flat20', User::factory()->create(), Car::factory()->create(['price_per_day' => 100]))->total_price)->toBe(295.0);
});

it('enforces promo windows, minimum spend and first-booking rules', function (array $attrs, string $message) {
    PromoCode::create(array_merge(['code' => 'TEST', 'type' => 'fixed', 'value' => 10, 'per_user_limit' => 0], $attrs));

    if ($attrs['first_booking_only'] ?? false) {
        book(car: Car::factory()->create());
    }

    try {
        book(promo: 'TEST');
        $this->fail('Expected the promo code to be refused.');
    } catch (ValidationException $e) {
        expect($e->errors()['promo_code'][0])->toContain($message);
    }
})->with([
    'expired' => [['ends_at' => now()->subDay()], 'expired'],
    'not started' => [['starts_at' => now()->addDay()], 'active yet'],
    'inactive' => [['is_active' => false], 'isn\'t valid'],
    'min spend' => [['min_spend' => 500], 'Spend at least RM 500.00'],
    'first booking' => [['first_booking_only' => true], 'first booking'],
]);

it('frees up redemptions when a booking is cancelled', function () {
    PromoCode::create(['code' => 'ONCE', 'type' => 'fixed', 'value' => 10, 'per_user_limit' => 1, 'max_redemptions' => 1]);

    $first = book(promo: 'ONCE');
    $second = Car::factory()->create();
    expect(fn () => book(promo: 'ONCE', user: User::factory()->create(), car: $second))->toThrow(ValidationException::class, 'fully redeemed');

    $first->cancel($this->user, 'Changed plans');
    expect(book(promo: 'ONCE', user: User::factory()->create(), car: $second)->discount_amount)->toBe(10.0);
});

it('limits uses per customer', function () {
    $other = Car::factory()->create(['price_per_day' => 100]);
    PromoCode::create(['code' => 'TWICE', 'type' => 'fixed', 'value' => 10, 'per_user_limit' => 1]);

    book(promo: 'TWICE');
    expect(fn () => app(BookingCreationService::class)->create($this->user, [$other->id], $this->start, $this->end, null, [], 'TWICE'))
        ->toThrow(ValidationException::class, 'already used');
});

it('re-prices add-ons and the discount when a booking is re-dated', function () {
    PromoCode::create(['code' => 'TEN', 'type' => 'percent', 'value' => 10, 'per_user_limit' => 0]);
    $booking = book([$this->seat->id => 1], 'TEN'); // (300 + 30) × 0.9 = 297

    expect($booking->total_price)->toBe(297.0);

    app(BookingCreationService::class)->update($booking, [$this->car->id], $this->start, now()->addDays(8)->toDateString());
    $booking->refresh();

    // 4 days: (400 + 40) × 0.9
    expect($booking->addons_amount)->toBe(40.0)
        ->and($booking->discount_amount)->toBe(44.0)
        ->and($booking->total_price)->toBe(396.0);
});

it('puts add-ons and the discount on the tax invoice', function () {
    PromoCode::create(['code' => 'FLAT20', 'type' => 'fixed', 'value' => 20, 'per_user_limit' => 0]);
    $booking = book([$this->seat->id => 1], 'FLAT20'); // 300 + 30 − 20 = 310
    $booking->update(['status' => 'approved']);

    $payment = app(PaymentService::class)->recordCounterPayment($booking, 'booking', 'cash', null, branchStaff($this->branch));
    $invoice = $payment->invoices()->firstOrFail();
    $lines = collect($invoice->lines);

    expect($invoice->total)->toEqual(310.0)
        ->and($lines)->toHaveCount(3)
        ->and($lines->sum('amount'))->toEqual(310.0)
        ->and($lines->last()['description'])->toContain('FLAT20')
        ->and($lines->last()['amount'])->toEqual(-20.0);
});

it('lets customers add extras and apply a code in the booking flow', function () {
    PromoCode::create(['code' => 'HELLO10', 'type' => 'percent', 'value' => 10, 'per_user_limit' => 1]);
    $this->actingAs($this->user);

    Livewire::test(CarAvailabilityPicker::class, ['preselectedCarId' => $this->car->id])
        ->set('startDate', $this->start)
        ->set('endDate', $this->end)
        ->call('setAddon', $this->seat->id, 5) // clamped to max 2
        ->assertSet('addons', [$this->seat->id => 2])
        ->set('promoInput', 'nope')
        ->call('applyPromo')
        ->assertHasErrors('promo_code')
        ->set('promoInput', 'hello10')
        ->call('applyPromo')
        ->assertHasNoErrors('promo_code')
        ->assertSet('appliedPromo', 'HELLO10')
        ->assertViewHas('quote', fn ($q) => $q['total'] === 324.0) // (300 + 60) × 0.9
        ->set('acceptTerms', true)
        ->call('createBooking')
        ->assertRedirect();

    $booking = Booking::firstOrFail();
    expect($booking->total_price)->toBe(324.0)->and($booking->addons()->sum('quantity'))->toEqual(2);
});

it('accepts add-ons and a promo code through the API', function () {
    PromoCode::create(['code' => 'API5', 'type' => 'fixed', 'value' => 5, 'per_user_limit' => 0]);

    $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/bookings', [
        'start_date' => $this->start, 'end_date' => $this->end, 'cars' => [$this->car->id],
        'addons' => [['id' => $this->driver->id, 'quantity' => 1]], 'promo_code' => 'api5',
    ])->assertCreated()
        ->assertJsonPath('data.total_price', 310)
        ->assertJsonPath('data.addons_amount', 15)
        ->assertJsonPath('data.discount_amount', 5)
        ->assertJsonPath('data.promo_code', 'API5');

    $this->actingAs(User::factory()->create(), 'sanctum')->postJson('/api/v1/bookings', [
        'start_date' => now()->addDays(20)->toDateString(), 'end_date' => now()->addDays(21)->toDateString(),
        'cars' => [$this->car->id], 'promo_code' => 'BOGUS',
    ])->assertUnprocessable()->assertJsonValidationErrors('promo_code');
});

it('creates the default extras and launch codes on migrate, without overwriting admin edits', function () {
    // RefreshDatabase ran every migration, including the data migration.
    expect(Addon::whereIn('name', ['Full protection (CDW)', 'Child seat', 'GPS navigator', 'Additional driver'])->count())->toBe(4)
        ->and(PromoCode::whereIn('code', ['WELCOME10', 'WEEKEND20'])->count())->toBe(2);

    Addon::where('name', 'GPS navigator')->update(['price' => 12]);
    PromoCode::where('code', 'WELCOME10')->update(['is_active' => false]);

    (new CustomerExperienceSeeder)->run(); // e.g. a later db:seed

    expect(Addon::where('name', 'GPS navigator')->count())->toBe(1)
        ->and(Addon::where('name', 'GPS navigator')->value('price'))->toEqual(12)
        ->and(PromoCode::where('code', 'WELCOME10')->value('is_active'))->toBeFalsy();
});
