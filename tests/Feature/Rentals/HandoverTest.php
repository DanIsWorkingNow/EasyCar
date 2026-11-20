<?php

require_once __DIR__.'/RentalHelpers.php';

use App\Livewire\Ops\HandoverForm;
use App\Models\Branch;
use App\Models\Car;
use App\Models\Payment;
use App\Models\VehicleInspection;
use App\Notifications\PickupCompleted;
use App\Notifications\ReturnCompleted;
use App\Services\Rentals\HandoverData;
use App\Services\Rentals\RentalOperationsService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('local');
    Storage::fake('public');
    Notification::fake();

    $this->branch = Branch::factory()->create();
    $this->car = Car::factory()->create(['branch_id' => $this->branch->id, 'price_per_day' => 100]);
    $this->staff = branchStaff($this->branch);
    $this->booking = approvedBooking([$this->car]);
});

function pickUpViaService(array $overrides = []): VehicleInspection
{
    return app(RentalOperationsService::class)->pickUp(
        test()->booking, test()->car, test()->staff,
        new HandoverData(...array_merge(['odometer' => 12000, 'fuel' => 8, 'signature' => signaturePng(), 'signedByName' => 'Customer'], $overrides)),
    );
}

it('hands a car over through the Livewire form: inspection, photos, signature, car rented, customer notified', function () {
    at('2026-12-10 10:30');

    Livewire::actingAs($this->staff)
        ->test(HandoverForm::class, ['booking' => $this->booking, 'car' => $this->car, 'type' => 'pickup'])
        ->set('odometer', 12000)
        ->call('setFuel', 7)
        ->call('cycleDamage', 'bonnet')          // scratch
        ->call('cycleDamage', 'rear_bumper')
        ->call('cycleDamage', 'rear_bumper')     // dent
        ->set('photos', [UploadedFile::fake()->image('front.jpg'), UploadedFile::fake()->image('dash.jpg')])
        ->set('licenceChecked', true)
        ->set('signature', signaturePng())
        ->call('submit')
        ->assertHasNoErrors();

    $inspection = VehicleInspection::firstOrFail();
    expect($inspection->type)->toBe('pickup')
        ->and($inspection->odometer_km)->toBe(12000)
        ->and($inspection->fuel_level)->toBe(7)
        ->and($inspection->damages)->toBe(['bonnet' => 'scratch', 'rear_bumper' => 'dent'])
        ->and($inspection->photos)->toHaveCount(2)
        ->and($inspection->performed_by)->toBe($this->staff->id);

    Storage::disk('local')->assertExists($inspection->signature_path);
    Storage::disk('local')->assertExists($inspection->photos->first()->path);
    expect(Storage::disk('public')->allFiles())->toBeEmpty(); // nothing public

    expect($this->car->fresh()->status)->toBe('rented')
        ->and($this->booking->fresh()->picked_up_at)->not->toBeNull()
        ->and($this->booking->fresh()->isOnTrip())->toBeTrue();

    Notification::assertSentTo($this->booking->user, PickupCompleted::class);
});

it('requires the licence check and a signature before handing over', function () {
    at('2026-12-10 10:30');

    Livewire::actingAs($this->staff)
        ->test(HandoverForm::class, ['booking' => $this->booking, 'car' => $this->car, 'type' => 'pickup'])
        ->set('odometer', 12000)
        ->call('submit')
        ->assertHasErrors(['licenceChecked', 'signature']);

    expect(VehicleInspection::count())->toBe(0);
});

it('blocks pick-up before the start date and for unconfirmed bookings', function () {
    at('2026-12-09 15:00');
    expect(fn () => pickUpViaService())->toThrow(ValidationException::class);

    at('2026-12-10 10:00');
    $this->booking->update(['status' => 'pending']);
    expect(fn () => pickUpViaService())->toThrow(ValidationException::class);

    expect(VehicleInspection::count())->toBe(0);
});

it('makes staff take payment at the counter when the booking is unpaid', function () {
    at('2026-12-10 10:30');
    $this->booking->update(['payment_status' => 'unpaid']);

    Livewire::actingAs($this->staff)
        ->test(HandoverForm::class, ['booking' => $this->booking, 'car' => $this->car, 'type' => 'pickup'])
        ->set('odometer', 500)->set('licenceChecked', true)->set('signature', signaturePng())
        ->call('submit')
        ->assertHasErrors('paymentMethod')
        ->set('paymentMethod', 'card_terminal')
        ->set('paymentReference', 'APPR-7781')
        ->call('submit')
        ->assertHasNoErrors();

    $payment = Payment::firstOrFail();
    expect($payment->gateway)->toBe('counter')
        ->and($payment->purpose)->toBe('booking')
        ->and($payment->amount)->toEqual(300.0)
        ->and($payment->gateway_reference)->toBe('APPR-7781')
        ->and($payment->meta['method'])->toBe('card_terminal')
        ->and($this->booking->fresh()->isPaid())->toBeTrue();
});

it('rejects an odometer reading below the car\'s last recorded reading', function () {
    at('2026-12-10 10:30');
    VehicleInspection::create([
        'booking_id' => approvedBooking([$this->car], ['start_date' => '2026-11-01', 'end_date' => '2026-11-02'])->id,
        'car_id' => $this->car->id, 'type' => 'return', 'odometer_km' => 15000, 'fuel_level' => 8,
        'performed_at' => now()->subWeek(),
    ]);

    expect(fn () => pickUpViaService(['odometer' => 14000]))->toThrow(ValidationException::class);
});

it('checks a car in on time with no charges and makes it available again', function () {
    at('2026-12-10 10:30');
    pickUpViaService();

    at('2026-12-12 17:30');
    Livewire::actingAs($this->staff)
        ->test(HandoverForm::class, ['booking' => $this->booking->fresh(), 'car' => $this->car, 'type' => 'return'])
        ->assertSet('odometer', '12000')          // prefilled from pick-up
        ->set('odometer', 12450)
        ->assertSet('fuel', 8)
        ->set('signature', signaturePng())
        ->call('submit')
        ->assertHasNoErrors();

    $booking = $this->booking->fresh();
    expect($booking->status)->toBe('completed')
        ->and($booking->completed_at)->not->toBeNull()
        ->and($booking->charges)->toHaveCount(0)
        ->and($this->car->fresh()->status)->toBe('available');

    Notification::assertSentTo($booking->user, ReturnCompleted::class);
});

it('charges late return, fuel and staff-added damage, and can send the car to maintenance', function () {
    at('2026-12-10 10:30');
    pickUpViaService(['fuel' => 8]);

    // Due 12 Dec 18:00; back 13 Dec 20:00 = 26 h late → 2 started days.
    at('2026-12-13 20:00');
    app(RentalOperationsService::class)->returnCar(
        $this->booking->fresh(), $this->car, $this->staff,
        new HandoverData(odometer: 12600, fuel: 5, damages: ['left_front' => 'dent'], signature: signaturePng(), signedByName: 'Customer'),
        [['type' => 'damage', 'description' => 'Dent, left front door', 'amount' => 250]],
        needsMaintenance: true,
    );

    $charges = $this->booking->fresh()->charges->keyBy(fn ($c) => $c->type->value);
    expect($charges)->toHaveCount(3)
        ->and($charges['late_return']->amount)->toEqual(200.0)   // 2 × RM100
        ->and($charges['late_return']->quantity)->toEqual(2.0)
        ->and($charges['fuel']->amount)->toEqual(45.0)           // 3/8 × RM15
        ->and($charges['damage']->amount)->toEqual(250.0)
        ->and($this->booking->fresh()->outstandingCharges())->toEqual(495.0)
        ->and($this->car->fresh()->status)->toBe('maintenance');

    $return = VehicleInspection::where('type', 'return')->first();
    $pickup = VehicleInspection::where('type', 'pickup')->first();
    expect($return->newDamagesComparedTo($pickup))->toBe(['left_front' => 'dent']);
});

it('rejects a return odometer below the pick-up reading', function () {
    at('2026-12-10 10:30');
    pickUpViaService(['odometer' => 12000]);

    at('2026-12-12 12:00');
    expect(fn () => app(RentalOperationsService::class)->returnCar(
        $this->booking->fresh(), $this->car, $this->staff,
        new HandoverData(odometer: 11000, fuel: 8, signature: signaturePng()),
    ))->toThrow(ValidationException::class);
});

it('completes a two-car booking only once both cars are back', function () {
    $second = Car::factory()->create(['branch_id' => $this->branch->id, 'price_per_day' => 80]);
    $booking = approvedBooking([$this->car, $second]);
    $ops = app(RentalOperationsService::class);
    $data = fn (int $km) => new HandoverData(odometer: $km, fuel: 8, signature: signaturePng());

    at('2026-12-10 10:30');
    $ops->pickUp($booking, $this->car, $this->staff, $data(100));
    $ops->pickUp($booking->fresh(), $second, $this->staff, $data(200));

    at('2026-12-12 16:00');
    $ops->returnCar($booking->fresh(), $this->car, $this->staff, $data(300));
    expect($booking->fresh()->status)->toBe('approved')->and($booking->fresh()->isOnTrip())->toBeTrue();

    $ops->returnCar($booking->fresh(), $second, $this->staff, $data(400));
    expect($booking->fresh()->status)->toBe('completed');
});

it('stops staff from another branch handing over the car', function () {
    $otherStaff = branchStaff(Branch::factory()->create());

    $this->actingAs($otherStaff)->get(route('ops.pickup', [$this->booking, $this->car]))->assertForbidden();
    $this->actingAs($this->staff)->get(route('ops.pickup', [$this->booking, $this->car]))->assertOk();
    $this->actingAs(adminUser())->get(route('ops.pickup', [$this->booking, $this->car]))->assertOk();
});

it('stops a customer cancelling once the car has been picked up', function () {
    at('2026-12-10 10:30');
    pickUpViaService();

    expect($this->booking->fresh()->canBeCancelledBy($this->booking->user))->toBeFalse();
});

it('exposes trip status through the API', function () {
    at('2026-12-10 10:30');
    pickUpViaService();
    $token = $this->booking->user->createToken('t')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/v1/bookings/{$this->booking->id}")
        ->assertOk()
        ->assertJsonPath('data.trip_status', 'on_trip');
});
