<?php

use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Car;
use App\Models\Payment;
use App\Models\User;
use App\Payments\Gateways\BillplzGateway;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function bookingForGateway(): Booking
{
    $car = Car::factory()->create(['price_per_day' => 123.45]);
    $booking = Booking::factory()->create([
        'user_id' => User::factory()->create()->id,
        'start_date' => now()->addDays(4)->toDateString(),
        'end_date' => now()->addDays(4)->toDateString(),
        'total_days' => 1,
        'total_price' => 123.45,
    ]);
    $booking->cars()->attach($car->id, ['rental_start' => $booking->start_date, 'rental_end' => $booking->end_date, 'quantity' => 1, 'price' => 123.45]);

    return $booking->fresh();
}

describe('stripe', function () {
    beforeEach(function () {
        config([
            'payments.gateways.stripe.enabled' => true,
            'payments.gateways.stripe.secret' => 'sk_test_123',
            'payments.gateways.stripe.webhook_secret' => 'whsec_test',
        ]);
    });

    function stripeSign(string $payload, ?int $t = null): string
    {
        $t ??= time();

        return "t={$t},v1=".hash_hmac('sha256', "{$t}.{$payload}", 'whsec_test');
    }

    it('creates a checkout session with the amount in sen and an idempotency key', function () {
        Http::fake(['api.stripe.com/v1/checkout/sessions' => Http::response(['id' => 'cs_test_1', 'url' => 'https://checkout.stripe.com/c/pay/cs_test_1'])]);

        $payment = app(PaymentService::class)->startCheckout(bookingForGateway(), 'stripe');

        expect($payment->checkout_url)->toBe('https://checkout.stripe.com/c/pay/cs_test_1')
            ->and($payment->gateway_reference)->toBe('cs_test_1');

        Http::assertSent(fn (HttpRequest $r) => str_contains($r->url(), '/checkout/sessions')
            && $r['line_items'][0]['price_data']['unit_amount'] === 12345
            && $r['line_items'][0]['price_data']['currency'] === 'myr'
            && $r['client_reference_id'] === $payment->uuid
            && $r->hasHeader('Idempotency-Key', 'checkout-'.$payment->uuid));
    });

    it('marks paid on a correctly signed checkout.session.completed event', function () {
        Http::fake(['api.stripe.com/*' => Http::response(['id' => 'cs_test_2', 'url' => 'https://checkout.stripe.com/x'])]);
        $payment = app(PaymentService::class)->startCheckout(bookingForGateway(), 'stripe');

        $payload = json_encode(['id' => 'evt_1', 'type' => 'checkout.session.completed', 'data' => ['object' => [
            'id' => 'cs_test_2', 'client_reference_id' => $payment->uuid, 'payment_status' => 'paid', 'payment_intent' => 'pi_1',
        ]]]);

        $this->call('POST', '/payments/webhook/stripe', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_STRIPE_SIGNATURE' => stripeSign($payload)], $payload)->assertOk();

        expect($payment->fresh()->status)->toBe(PaymentStatus::Paid)
            ->and($payment->fresh()->meta['payment_intent'])->toBe('pi_1');
    });

    it('rejects forged and replayed (stale) Stripe signatures', function () {
        $payload = json_encode(['id' => 'evt_2', 'type' => 'checkout.session.completed', 'data' => ['object' => []]]);

        $this->call('POST', '/payments/webhook/stripe', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_STRIPE_SIGNATURE' => 't='.time().',v1=deadbeef'], $payload)->assertStatus(400);
        $this->call('POST', '/payments/webhook/stripe', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_STRIPE_SIGNATURE' => stripeSign($payload, time() - 3600)], $payload)->assertStatus(400);
    });

    it('refunds through the Refunds API using the payment intent', function () {
        Http::fake([
            'api.stripe.com/v1/checkout/sessions' => Http::response(['id' => 'cs_test_3', 'url' => 'https://checkout.stripe.com/y']),
            'api.stripe.com/v1/refunds' => Http::response(['id' => 're_1', 'status' => 'succeeded']),
        ]);
        $payment = app(PaymentService::class)->startCheckout(bookingForGateway(), 'stripe');
        $payment->forceFill(['status' => PaymentStatus::Paid, 'meta' => ['payment_intent' => 'pi_9']])->save();

        app(PaymentService::class)->refund($payment, 'test');

        expect($payment->fresh()->status)->toBe(PaymentStatus::Refunded);
        Http::assertSent(fn (HttpRequest $r) => str_contains($r->url(), '/refunds') && $r['payment_intent'] === 'pi_9' && $r['amount'] === 12345);
    });

    it('fails gracefully when Stripe is down', function () {
        Http::fake(['api.stripe.com/*' => Http::response(['error' => ['message' => 'boom']], 500)]);
        $booking = bookingForGateway();

        $this->actingAs($booking->user)->post(route('bookings.pay', $booking), ['gateway' => 'stripe'])
            ->assertSessionHas('error');

        expect(Payment::first()->status)->toBe(PaymentStatus::Failed);
    });
});

describe('billplz', function () {
    beforeEach(function () {
        config([
            'payments.gateways.billplz.enabled' => true,
            'payments.gateways.billplz.api_key' => 'key',
            'payments.gateways.billplz.collection_id' => 'col',
            'payments.gateways.billplz.x_signature' => 'xsig-secret',
        ]);
        Http::fake(['www.billplz-sandbox.com/api/v3/bills' => Http::response(['id' => 'bill123', 'url' => 'https://www.billplz-sandbox.com/bills/bill123'])]);
    });

    it('accepts a correctly signed paid callback', function () {
        $payment = app(PaymentService::class)->startCheckout(bookingForGateway(), 'billplz');
        $gateway = new BillplzGateway(config('payments.gateways.billplz'));
        $data = ['id' => 'bill123', 'collection_id' => 'col', 'paid' => 'true', 'state' => 'paid', 'amount' => '12345', 'paid_amount' => '12345', 'paid_at' => '2026-11-18 10:00:00 +0800'];
        $data['x_signature'] = $gateway->sign($data);

        $this->post('/payments/webhook/billplz', $data)->assertOk();

        expect($payment->fresh()->status)->toBe(PaymentStatus::Paid);
    });

    it('rejects a tampered callback', function () {
        $payment = app(PaymentService::class)->startCheckout(bookingForGateway(), 'billplz');
        $gateway = new BillplzGateway(config('payments.gateways.billplz'));
        $data = ['id' => 'bill123', 'paid' => 'false', 'state' => 'due'];
        $data['x_signature'] = $gateway->sign($data);
        $data['paid'] = 'true'; // tampered after signing

        $this->post('/payments/webhook/billplz', $data)->assertStatus(400);
        expect($payment->fresh()->status)->toBe(PaymentStatus::Pending);
    });
});

describe('toyyibpay', function () {
    beforeEach(function () {
        config([
            'payments.gateways.toyyibpay.enabled' => true,
            'payments.gateways.toyyibpay.secret_key' => 'secret',
            'payments.gateways.toyyibpay.category_code' => 'cat',
        ]);
    });

    it('ignores a forged "paid" callback that the API does not confirm', function () {
        Http::fake([
            'dev.toyyibpay.com/index.php/api/createBill' => Http::response([['BillCode' => 'abc123']]),
            'dev.toyyibpay.com/index.php/api/getBillTransactions' => Http::response([]),
        ]);
        $payment = app(PaymentService::class)->startCheckout(bookingForGateway(), 'toyyibpay');

        $this->post('/payments/webhook/toyyibpay', ['billcode' => 'abc123', 'order_id' => $payment->uuid, 'status' => '1', 'refno' => 'R1'])->assertOk();

        expect($payment->fresh()->status)->toBe(PaymentStatus::Pending);
    });

    it('marks paid once the API confirms the transaction', function () {
        Http::fake(fn (HttpRequest $r) => str_contains($r->url(), 'createBill')
            ? Http::response([['BillCode' => 'abc999']])
            : Http::response([['billpaymentStatus' => '1', 'billExternalReferenceNo' => Payment::first()?->uuid, 'billpaymentInvoiceNo' => 'TP1']]));

        $payment = app(PaymentService::class)->startCheckout(bookingForGateway(), 'toyyibpay');
        expect($payment->checkout_url)->toBe('https://dev.toyyibpay.com/abc999');

        $this->post('/payments/webhook/toyyibpay', ['billcode' => 'abc999', 'order_id' => $payment->uuid, 'status' => '1', 'refno' => 'TP1'])->assertOk();

        expect($payment->fresh()->status)->toBe(PaymentStatus::Paid);
    });
});

it('404s webhooks for unknown gateways', function () {
    $this->post('/payments/webhook/nope', [])->assertNotFound();
});
