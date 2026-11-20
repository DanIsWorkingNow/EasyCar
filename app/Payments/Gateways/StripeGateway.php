<?php

namespace App\Payments\Gateways;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\Data\CheckoutSession;
use App\Payments\Data\GatewayResult;
use App\Payments\Data\RefundResult;
use App\Payments\Exceptions\InvalidSignatureException;
use App\Payments\Exceptions\PaymentException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Stripe Checkout over the REST API (no SDK dependency). Webhook
 * signatures follow Stripe's scheme: HMAC-SHA256 of "{t}.{payload}" with
 * the endpoint secret, compared against every v1= entry of the
 * Stripe-Signature header, with a replay tolerance window.
 */
class StripeGateway implements PaymentGateway
{
    public function __construct(private array $config) {}

    public function name(): string
    {
        return 'stripe';
    }

    public function isConfigured(): bool
    {
        return filled($this->config['secret'] ?? null) && filled($this->config['webhook_secret'] ?? null);
    }

    public function createCheckout(Payment $payment, array $urls): CheckoutSession
    {
        $payment->loadMissing(['booking.cars', 'user']);
        $booking = $payment->booking;

        $separator = str_contains($urls['return'], '?') ? '&' : '?';
        $title = ($payment->isForCharges() ? 'EasyCar extra charges ' : 'EasyCar booking ').$booking->reference;

        $response = $this->http()
            ->withHeaders(['Idempotency-Key' => 'checkout-'.$payment->uuid])
            ->asForm()
            ->post('/checkout/sessions', [
                'mode' => 'payment',
                'success_url' => $urls['return'].$separator.'session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => $urls['cancel'],
                'client_reference_id' => $payment->uuid,
                'customer_email' => $payment->user->email,
                // Stripe requires 30 minutes to 24 hours.
                'expires_at' => now()->addMinutes(max(30, min(1440, (int) config('payments.hold_minutes', 60))))->timestamp,
                'metadata' => [
                    'payment_uuid' => $payment->uuid,
                    'booking_reference' => $booking->reference,
                ],
                'payment_intent_data' => [
                    'description' => $title,
                    'metadata' => [
                        'payment_uuid' => $payment->uuid,
                        'booking_reference' => $booking->reference,
                    ],
                ],
                'line_items' => [[
                    'quantity' => 1,
                    'price_data' => [
                        'currency' => strtolower($payment->currency),
                        'unit_amount' => $payment->amountInMinorUnits(),
                        'product_data' => [
                            'name' => $title,
                            'description' => Str::limit(
                                $booking->car_names.' · '.$booking->start_date->format('d M').' – '.$booking->end_date->format('d M Y'),
                                250
                            ),
                        ],
                    ],
                ]],
            ]);

        if (! $response->successful() || ! $response->json('url')) {
            throw new PaymentException(
                'We could not start the card payment. Please try again or choose another method.',
                ['status' => $response->status(), 'error' => $response->json('error')]
            );
        }

        return new CheckoutSession($response->json('id'), $response->json('url'));
    }

    public function parseWebhook(Request $request): GatewayResult
    {
        $payload = $request->getContent();
        $this->verifySignature($payload, (string) $request->header('Stripe-Signature'));

        $event = json_decode($payload, true);

        if (! is_array($event) || empty($event['id']) || empty($event['type'])) {
            throw new InvalidSignatureException('Malformed Stripe event.');
        }

        $type = $event['type'];
        $object = $event['data']['object'] ?? [];
        $isSession = str_starts_with($type, 'checkout.session.');

        $status = match ($type) {
            'checkout.session.completed' => ($object['payment_status'] ?? null) === 'paid' ? PaymentStatus::Paid : null,
            'checkout.session.async_payment_succeeded' => PaymentStatus::Paid,
            'checkout.session.async_payment_failed' => PaymentStatus::Failed,
            'checkout.session.expired' => PaymentStatus::Cancelled,
            default => null,
        };

        return new GatewayResult(
            eventId: $event['id'],
            type: $type,
            status: $status,
            paymentUuid: $isSession ? ($object['client_reference_id'] ?? $object['metadata']['payment_uuid'] ?? null) : null,
            gatewayReference: $isSession ? ($object['id'] ?? null) : null,
            failureReason: match ($type) {
                'checkout.session.async_payment_failed' => 'The payment was declined by your bank.',
                'checkout.session.expired' => 'The checkout session expired.',
                default => null,
            },
            meta: array_filter(['payment_intent' => $object['payment_intent'] ?? null]),
            payload: $event,
        );
    }

    public function verifyReturn(Request $request, Payment $payment): ?GatewayResult
    {
        $sessionId = (string) $request->query('session_id', '');

        if ($sessionId === '' || ! hash_equals((string) $payment->gateway_reference, $sessionId)) {
            return null;
        }

        $session = $this->http()->get('/checkout/sessions/'.urlencode($sessionId));

        if (! $session->successful()) {
            return null;
        }

        $status = match (true) {
            $session->json('payment_status') === 'paid' => PaymentStatus::Paid,
            $session->json('status') === 'expired' => PaymentStatus::Cancelled,
            default => null,
        };

        if ($status === null) {
            return null;
        }

        return new GatewayResult(
            eventId: "return:{$sessionId}:{$status->value}",
            type: 'checkout.session.return',
            status: $status,
            paymentUuid: $payment->uuid,
            gatewayReference: $sessionId,
            meta: array_filter(['payment_intent' => $session->json('payment_intent')]),
            payload: ['session_id' => $sessionId, 'payment_status' => $session->json('payment_status')],
        );
    }

    public function supportsRefunds(): bool
    {
        return true;
    }

    public function refund(Payment $payment, float $amount, string $reason): RefundResult
    {
        $intent = $payment->meta['payment_intent'] ?? $this->http()
            ->get('/checkout/sessions/'.urlencode((string) $payment->gateway_reference))
            ->json('payment_intent');

        if (! $intent) {
            throw new PaymentException('Unable to locate the original Stripe charge for this refund.');
        }

        $minor = (int) round($amount * 100);

        $response = $this->http()
            ->withHeaders(['Idempotency-Key' => "refund-{$payment->uuid}-{$minor}"])
            ->asForm()
            ->post('/refunds', [
                'payment_intent' => $intent,
                'amount' => $minor,
                'metadata' => ['payment_uuid' => $payment->uuid, 'reason' => Str::limit($reason, 450)],
            ]);

        if (! $response->successful() || in_array($response->json('status'), ['failed', 'canceled'], true)) {
            throw new PaymentException('Stripe could not process the refund.', [
                'status' => $response->status(),
                'error' => $response->json('error') ?? $response->json('status'),
            ]);
        }

        return new RefundResult(true, $response->json('id'), ['refund_status' => $response->json('status')]);
    }

    private function verifySignature(string $payload, string $header): void
    {
        $timestamp = null;
        $signatures = [];

        foreach (explode(',', $header) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, null);

            if ($key === 't') {
                $timestamp = (int) $value;
            } elseif ($key === 'v1' && $value) {
                $signatures[] = $value;
            }
        }

        if (! $timestamp || $signatures === []) {
            throw new InvalidSignatureException('Missing Stripe signature.');
        }

        if (abs(time() - $timestamp) > (int) ($this->config['webhook_tolerance'] ?? 300)) {
            throw new InvalidSignatureException('Stripe signature timestamp outside tolerance.');
        }

        $expected = hash_hmac('sha256', "{$timestamp}.{$payload}", (string) $this->config['webhook_secret']);

        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) {
                return;
            }
        }

        throw new InvalidSignatureException('Stripe signature mismatch.');
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) ($this->config['base_url'] ?? 'https://api.stripe.com/v1'), '/'))
            ->withToken((string) $this->config['secret'])
            ->acceptJson()
            ->timeout(20)
            // Safe to retry: every POST carries an Idempotency-Key.
            ->retry(2, 300, fn ($e) => $e instanceof ConnectionException, throw: false);
    }
}
