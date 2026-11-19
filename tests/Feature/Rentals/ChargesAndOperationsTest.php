<?php

require_once __DIR__.'/RentalHelpers.php';

use App\Enums\ChargeType;
use App\Enums\PaymentStatus;
use App\Models\Branch;
use App\Models\Car;
use App\Models\InspectionPhoto;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\ReturnOverdue;
use App\Payments\PaymentManager;
use App\Services\PaymentService;
use App\Services\Rentals\HandoverData;
use App\Services\Rentals\RentalOperationsService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('local');

    $this->branch = Branch::factory()->create();
    $this->car = Car::factory()->create(['branch_id' => $this->branch->id, 'price_per_day' => 100]);
    $this->staff = branchStaff($this->branch);
    $this->booking = approvedBooking([$this->car]);
    $this->ops = app(RentalOperationsService::class);
});

/** Pick up on time, return a day late with less fuel → RM100 late + RM30 fuel. */
function tripWithCharges(): void
{
    at('2026-12-10 10:30');
    test()->ops->pickUp(test()->booking, test()->car, test()->staff, new HandoverData(odometer: 100, fuel: 8, signature: signaturePng(), photos: [UploadedFile::fake()->image('p.jpg')]));
    at('2026-12-13 10:00');
    test()->ops->returnCar(test()->booking->fresh(), test()->car, test()->staff, new HandoverData(odometer: 400, fuel: 6, signature: signaturePng()));
}

it('lets the customer pay outstanding extras online without touching the rental payment', function () {
    tripWithCharges();
    $booking = $this->booking->fresh();
    expect($booking->outstandingCharges())->toEqual(130.0);

    $this->actingAs($booking->user)->post(route('bookings.charges.pay', $booking), ['gateway' => 'sandbox'])->assertRedirect();
    $payment = Payment::where('purpose', 'charges')->firstOrFail();
    expect($payment->amount)->toEqual(130.0);

    $request = app(PaymentManager::class)->driver('sandbox')->simulatedWebhook($payment, 'success');
    $this->call('POST', '/payments/webhook/sandbox', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_SANDBOX_SIGNATURE' => $request->header('X-Sandbox-Signature')], $request->getContent())->assertOk();

    $booking->refresh();
    expect($booking->outstandingCharges())->toEqual(0.0)
        ->and($booking->charges->every->isPaid())->toBeTrue()
        ->and($booking->payment_status->value)->toBe('paid');   // rental status untouched
});

it('waives the charges when an extras payment is refunded, leaving the rental paid', function () {
    tripWithCharges();
    $payment = app(PaymentService::class)->startChargesCheckout($this->booking->fresh(), 'sandbox');
    $payment->forceFill(['status' => PaymentStatus::Paid])->save();
    $this->booking->charges()->update(['paid_at' => now(), 'payment_id' => $payment->id]);

    app(PaymentService::class)->refund($payment->fresh(), 'Disputed fuel');

    $booking = $this->booking->fresh();
    expect($booking->charges->every->isVoided())->toBeTrue()
        ->and($booking->payment_status->value)->toBe('paid');
});

it('records extras paid at the counter and waives individual charges', function () {
    tripWithCharges();
    $fuel = $this->booking->charges()->where('type', 'fuel')->first();

    $this->actingAs($this->staff)->post(route('ops.charges.void', $fuel), ['reason' => 'Customer goodwill'])->assertSessionHas('success');
    expect($fuel->fresh()->isVoided())->toBeTrue()->and($this->booking->fresh()->outstandingCharges())->toEqual(100.0);

    $this->actingAs($this->staff)->post(route('ops.charges.collect', $this->booking), ['method' => 'cash'])->assertSessionHas('success');
    expect($this->booking->fresh()->outstandingCharges())->toEqual(0.0);

    $counter = Payment::where('gateway', 'counter')->firstOrFail();
    expect($counter->purpose)->toBe('charges')->and($counter->amount)->toEqual(100.0);

    // Paid charges can't be waived.
    $late = $this->booking->charges()->where('type', 'late_return')->first();
    $this->actingAs($this->staff)->post(route('ops.charges.void', $late), ['reason' => 'x'])->assertSessionHasErrors('charge');
});

it('lets branch staff add a manual charge but not staff from another branch', function () {
    $payload = ['type' => 'cleaning', 'description' => 'Sand in the cabin', 'amount' => 60];

    $this->actingAs(branchStaff(Branch::factory()->create()))->post(route('ops.charges.store', $this->booking), $payload)->assertForbidden();
    $this->actingAs($this->staff)->post(route('ops.charges.store', $this->booking), $payload)->assertSessionHas('success');

    expect($this->booking->charges()->first()->type)->toBe(ChargeType::Cleaning);
});

it('alerts the customer, branch staff and admins once when a return is overdue', function () {
    Notification::fake();
    $admin = adminUser();
    $otherBranchStaff = branchStaff(Branch::factory()->create());

    at('2026-12-10 10:30');
    $this->ops->pickUp($this->booking, $this->car, $this->staff, new HandoverData(odometer: 100, fuel: 8, signature: signaturePng()));

    at('2026-12-12 18:30');   // within grace
    $this->artisan('rentals:flag-overdue')->assertSuccessful();
    Notification::assertNotSentTo($this->booking->user, ReturnOverdue::class);

    at('2026-12-12 19:30');
    $this->artisan('rentals:flag-overdue')->assertSuccessful();
    $this->artisan('rentals:flag-overdue')->assertSuccessful();   // idempotent

    Notification::assertSentToTimes($this->booking->user, ReturnOverdue::class, 1);
    Notification::assertSentToTimes($this->staff, ReturnOverdue::class, 1);
    Notification::assertSentToTimes($admin, ReturnOverdue::class, 1);
    Notification::assertNotSentTo($otherBranchStaff, ReturnOverdue::class);
});

it('shows handover reports, photos and signatures only to the customer, branch staff and admins', function () {
    tripWithCharges();
    $pickup = $this->booking->inspections()->where('type', 'pickup')->first();
    $photo = InspectionPhoto::firstOrFail();

    foreach ([$this->booking->user, $this->staff, adminUser()] as $allowed) {
        $this->actingAs($allowed)->get(route('inspections.show', $pickup))->assertOk()->assertSee($this->car->plate_number);
        $this->actingAs($allowed)->get(route('inspections.photo', [$pickup, $photo]))->assertOk();
        $this->actingAs($allowed)->get(route('inspections.signature', $pickup))->assertOk()->assertHeader('Content-Type', 'image/png');
    }

    foreach ([User::factory()->create(), branchStaff(Branch::factory()->create())] as $denied) {
        $this->actingAs($denied)->get(route('inspections.show', $pickup))->assertForbidden();
        $this->actingAs($denied)->get(route('inspections.photo', [$pickup, $photo]))->assertForbidden();
        $this->actingAs($denied)->get(route('inspections.signature', $pickup))->assertForbidden();
    }
});

it('sorts the operations board by what needs doing, scoped to the staff member\'s branch', function () {
    $otherBranch = Branch::factory()->create();
    $foreignCar = Car::factory()->create(['branch_id' => $otherBranch->id]);
    approvedBooking([$foreignCar]);

    $onTripCar = Car::factory()->create(['branch_id' => $this->branch->id]);
    $onTrip = approvedBooking([$onTripCar], ['start_date' => '2026-12-08', 'end_date' => '2026-12-11']);
    at('2026-12-08 10:00');
    $this->ops->pickUp($onTrip, $onTripCar, $this->staff, new HandoverData(odometer: 1, fuel: 8, signature: signaturePng()));

    at('2026-12-10 09:00');
    $board = $this->ops->board($this->branch->id);
    expect($board['pickups_today'])->toHaveCount(1)
        ->and($board['pickups_today'][0]['car']->id)->toBe($this->car->id)
        ->and($board['on_trip'])->toHaveCount(1);

    $this->actingAs($this->staff)->get(route('ops.board'))->assertOk()
        ->assertSee($this->car->plate_number)->assertSee('Start pick-up')
        ->assertDontSee($foreignCar->plate_number);

    $this->actingAs(adminUser())->get(route('ops.board'))->assertOk()->assertSee($foreignCar->plate_number);

    at('2026-12-12 09:00');
    expect($this->ops->board($this->branch->id)['overdue'])->toHaveCount(1);   // onTrip was due 11 Dec

    $this->actingAs($this->staff)->get(route('staff.dashboard'))->assertOk()->assertSee("Today's operations", false);
});

it('shows the trip, reports and extras on the customer\'s booking page', function () {
    tripWithCharges();

    $this->actingAs($this->booking->user)->get(route('bookings.show', $this->booking))->assertOk()
        ->assertSee('Pick-up report')->assertSee('Return report')
        ->assertSee('Extra charges')->assertSee('Pay RM 130.00');

    $this->actingAs($this->staff)->get(route('staff.bookings.show', $this->booking))->assertOk()
        ->assertSee('Trip &amp; handover', false)->assertSee('Record RM 130.00 paid');
});
