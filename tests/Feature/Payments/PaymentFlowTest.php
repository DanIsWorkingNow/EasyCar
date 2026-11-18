<?php

use App\Enums\BookingPaymentStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Car;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\User;
use App\Notifications\PaymentReceived;
use App\Notifications\PaymentRefunded;
use App\Payments\PaymentManager;
use App\Services\PaymentService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

function pendingBooking(?User $user = null, float $price = 100): Booking
{
    $car = Car::factory()->create(['price_per_day' => $price]);
    $booking = Booking::factory()->create([
        'user_id' => ($user ?? User::factory()->create())->id,
        'start_date' => now()->addDays(4)->toDateString(),
        'end_date' => now()->addDays(6)->toDateString(),
        'total_days' => 3,
        'total_price' => $price * 3,
    ]);
    $booking->cars()->attach($car->id, [
        'rental_start' => $booking->start_date, 'rental_end' => $booking->end_date,
        'quantity' => 1, 'price' => $price * 3,
    ]);

    return $booking->fresh();
}

function sandboxOutcome(Payment $payment, string $outcome)
{
    $gateway = app(PaymentManager::class)->driver('sandbox');
    $request = $gateway->simulatedWebhook($payment, $outcome);

    return test()->call('POST', '/payments/webhook/sandbox', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_SANDBOX_SIGNATURE' => $request->header('X-Sandbox-Signature'),
    ], $request->getContent());
}

it('starts a sandbox checkout and redirects to the hosted page', function () {
    $booking = pendingBooking();

    $response = $this->actingAs($booking->user)->post(route('bookings.pay', $booking), ['gateway' => 'sandbox']);

    $payment = Payment::firstOrFail();
    $response->assertRedirect(route('payments.sandbox.show', $payment));
    expect($payment->status)->toBe(PaymentStatus::Pending)
        ->and($payment->amount)->toEqual(300.0)
        ->and($payment->gateway_reference)->toStartWith('sbx_');
});

it('marks the payment and booking paid from a signed webhook and notifies once', function () {
    Notification::fake();
    $booking = pendingBooking();
    $this->actingAs($booking->user)->post(route('bookings.pay', $booking), ['gateway' => 'sandbox']);
    $payment = Payment::firstOrFail();

    $this->actingAs($booking->user)->post(route('payments.sandbox.complete', $payment), ['outcome' => 'success'])
        ->assertRedirect(route('payments.return', $payment));

    expect($payment->fresh()->status)->toBe(PaymentStatus::Paid)
        ->and($payment->fresh()->paid_at)->not->toBeNull()
        ->and($booking->fresh()->payment_status)->toBe(BookingPaymentStatus::Paid);
    Notification::assertSentToTimes($booking->user, PaymentReceived::class, 1);
});

it('treats a replayed gateway event as a no-op (idempotent)', function () {
    Notification::fake();
    $booking = pendingBooking();
    $payment = app(PaymentService::class)->startCheckout($booking, 'sandbox');

    $gateway = app(PaymentManager::class)->driver('sandbox');
    $request = $gateway->simulatedWebhook($payment, 'success');
    $server = ['CONTENT_TYPE' => 'application/json', 'HTTP_X_SANDBOX_SIGNATURE' => $request->header('X-Sandbox-Signature')];

    $this->call('POST', '/payments/webhook/sandbox', [], [], [], $server, $request->getContent())->assertOk();
    $this->call('POST', '/payments/webhook/sandbox', [], [], [], $server, $request->getContent())->assertOk();

    expect(PaymentEvent::count())->toBe(1);
    Notification::assertSentToTimes($booking->user, PaymentReceived::class, 1);
});

it('rejects webhooks with a bad signature', function () {
    $booking = pendingBooking();
    $payment = app(PaymentService::class)->startCheckout($booking, 'sandbox');

    $this->call('POST', '/payments/webhook/sandbox', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_SANDBOX_SIGNATURE' => 'forged',
    ], json_encode(['payment' => $payment->uuid, 'outcome' => 'success', 'event_id' => 'x']))->assertStatus(400);

    expect($payment->fresh()->status)->toBe(PaymentStatus::Pending);
});

it('never lets a late failure notification downgrade a paid payment', function () {
    $booking = pendingBooking();
    $payment = app(PaymentService::class)->startCheckout($booking, 'sandbox');

    sandboxOutcome($payment, 'success')->assertOk();
    sandboxOutcome($payment, 'failure')->assertOk();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Paid);
});

it('records a failed payment and leaves the booking payable', function () {
    $booking = pendingBooking();
    $payment = app(PaymentService::class)->startCheckout($booking, 'sandbox');

    sandboxOutcome($payment, 'failure')->assertOk();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Failed)
        ->and($booking->fresh()->canBePaid())->toBeTrue();
});

it('stops customers paying for or viewing other customers\' bookings and payments', function () {
    $booking = pendingBooking();
    $payment = app(PaymentService::class)->startCheckout($booking, 'sandbox');
    $intruder = User::factory()->create();

    $this->actingAs($intruder)->post(route('bookings.pay', $booking), ['gateway' => 'sandbox'])->assertForbidden();
    $this->actingAs($intruder)->get(route('payments.return', $payment))->assertForbidden();
    $this->actingAs($intruder)->post(route('payments.sandbox.complete', $payment), ['outcome' => 'success'])->assertForbidden();
});

it('refuses gateways that are not configured', function () {
    $booking = pendingBooking();

    $this->actingAs($booking->user)->post(route('bookings.pay', $booking), ['gateway' => 'stripe'])
        ->assertSessionHasErrors('gateway');

    expect(Payment::count())->toBe(0);
});

it('does not allow paying a booking twice', function () {
    $booking = pendingBooking();
    $payment = app(PaymentService::class)->startCheckout($booking, 'sandbox');
    sandboxOutcome($payment, 'success');

    $this->actingAs($booking->user)->post(route('bookings.pay', $booking->fresh()), ['gateway' => 'sandbox'])
        ->assertSessionHas('error');

    expect(Payment::count())->toBe(1);
});

it('refunds automatically when a paid booking is rejected', function () {
    Notification::fake();
    $this->seed(RolesAndPermissionsSeeder::class);
    $admin = User::factory()->create(['userLevel' => 5]);
    $admin->assignRole('admin');

    $booking = pendingBooking();
    $payment = app(PaymentService::class)->startCheckout($booking, 'sandbox');
    sandboxOutcome($payment, 'success');

    $this->actingAs($admin)->patch(route('bookings.reject', $booking), ['rejection_reason' => 'Car in service'])->assertRedirect();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Refunded)
        ->and($payment->fresh()->refunded_amount)->toEqual(300.0)
        ->and($booking->fresh()->payment_status)->toBe(BookingPaymentStatus::Refunded);
    Notification::assertSentTo($booking->user, PaymentRefunded::class);
});

it('refunds automatically when the customer cancels a paid booking', function () {
    $booking = pendingBooking();
    $payment = app(PaymentService::class)->startCheckout($booking, 'sandbox');
    sandboxOutcome($payment, 'success');

    $this->actingAs($booking->user)->delete(route('bookings.destroy', $booking), ['reason' => 'Plans changed'])
        ->assertRedirect(route('bookings.index'));

    $booking->refresh();
    expect($booking->status)->toBe('cancelled')
        ->and($booking->trashed())->toBeFalse()
        ->and($booking->payment_status)->toBe(BookingPaymentStatus::Refunded)
        ->and($payment->fresh()->status)->toBe(PaymentStatus::Refunded);
});

it('expires unpaid pending bookings after the hold window but keeps paid and fresh ones', function () {
    config(['payments.hold_minutes' => 60]);

    $stale = pendingBooking();
    $stale->forceFill(['created_at' => now()->subHours(2)])->save();

    $fresh = pendingBooking();

    $paid = pendingBooking();
    $paid->forceFill(['created_at' => now()->subHours(2), 'payment_status' => 'paid'])->save();

    $this->artisan('bookings:expire-unpaid')->assertSuccessful();

    expect($stale->fresh()->status)->toBe('cancelled')
        ->and($fresh->fresh()->status)->toBe('pending')
        ->and($paid->fresh()->status)->toBe('pending');
});

it('lets an admin refund from the payments console', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $admin = User::factory()->create(['userLevel' => 5]);
    $admin->assignRole('admin');

    $booking = pendingBooking();
    $payment = app(PaymentService::class)->startCheckout($booking, 'sandbox');
    sandboxOutcome($payment, 'success');

    $this->actingAs($admin)->get(route('admin.payments.index'))->assertOk()->assertSee($booking->reference);
    $this->actingAs($admin)->get(route('admin.payments.show', $payment))->assertOk();
    $this->actingAs($admin)->post(route('admin.payments.refund', $payment), ['reason' => 'Goodwill'])->assertSessionHas('success');

    expect($payment->fresh()->status)->toBe(PaymentStatus::Refunded);
});
