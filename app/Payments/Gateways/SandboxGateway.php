<?php

namespace App\Payments\Gateways;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\Data\CheckoutSession;
use App\Payments\Data\GatewayResult;
use App\Payments\Data\RefundResult;
use App\Payments\Exceptions\InvalidSignatureException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * A simulated hosted checkout for local development, demos and tests.
 * Its "webhook" is HMAC-signed with the app key and goes through exactly
 * the same PaymentService pipeline (signature check -> idempotent event
 * log -> state machine) as the real gateways.
 */
class SandboxGateway implements PaymentGateway
{
    public const OUTCOMES = ['success', 'failure', 'cancel'];

    public function __construct(private string $signingKey) {}

    public function name(): string
    {
        return 'sandbox';
    }

    public function isConfigured(): bool
    {
        return $this->signingKey !== '';
    }

    public function createCheckout(Payment $payment, array $urls): CheckoutSession
    {
        return new CheckoutSession(
            'sbx_'.Str::lower(Str::random(24)),
            route('payments.sandbox.show', $payment),
        );
    }

    public function parseWebhook(Request $request): GatewayResult
    {
        $payload = $request->getContent();

        if (! hash_equals($this->sign($payload), (string) $request->header('X-Sandbox-Signature'))) {
            throw new InvalidSignatureException('Sandbox signature mismatch.');
        }

        $data = json_decode($payload, true);

        if (! is_array($data) || ! in_array($data['outcome'] ?? null, self::OUTCOMES, true) || empty($data['payment'])) {
            throw new InvalidSignatureException('Malformed sandbox event.');
        }

        return new GatewayResult(
            eventId: (string) ($data['event_id'] ?? Str::uuid()),
            type: 'sandbox.'.$data['outcome'],
            status: match ($data['outcome']) {
                'success' => PaymentStatus::Paid,
                'failure' => PaymentStatus::Failed,
                'cancel' => PaymentStatus::Cancelled,
            },
            paymentUuid: (string) $data['payment'],
            gatewayReference: $data['reference'] ?? null,
            failureReason: match ($data['outcome']) {
                'failure' => 'Card declined (simulated).',
                'cancel' => 'Checkout cancelled.',
                default => null,
            },
            payload: $data,
        );
    }

    public function verifyReturn(Request $request, Payment $payment): ?GatewayResult
    {
        return null; // The signed webhook above is the only source of truth.
    }

    public function supportsRefunds(): bool
    {
        return true;
    }

    public function refund(Payment $payment, float $amount, string $reason): RefundResult
    {
        return new RefundResult(true, 'sbx_rf_'.Str::lower(Str::random(16)));
    }

    /**
     * Build a signed webhook request for a simulated outcome.
     */
    public function simulatedWebhook(Payment $payment, string $outcome): Request
    {
        $body = json_encode([
            'event_id' => 'sbx_evt_'.Str::lower(Str::random(20)),
            'payment' => $payment->uuid,
            'reference' => $payment->gateway_reference,
            'outcome' => $outcome,
        ], JSON_THROW_ON_ERROR);

        return Request::create('/payments/webhook/sandbox', 'POST', server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_SANDBOX_SIGNATURE' => $this->sign($body),
        ], content: $body);
    }

    public function sign(string $payload): string
    {
        return hash_hmac('sha256', $payload, $this->signingKey);
    }
}
