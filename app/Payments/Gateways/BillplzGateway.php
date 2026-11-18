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
 * Billplz v3 bills (Malaysian FPX). Both the server callback and the
 * browser redirect carry an X-Signature: HMAC-SHA256 over the
 * "keyvalue" pairs sorted case-insensitively and joined with "|".
 */
class BillplzGateway implements PaymentGateway
{
    public function __construct(private array $config) {}

    public function name(): string
    {
        return 'billplz';
    }

    public function isConfigured(): bool
    {
        return filled($this->config['api_key'] ?? null)
            && filled($this->config['collection_id'] ?? null)
            && filled($this->config['x_signature'] ?? null);
    }

    public function createCheckout(Payment $payment, array $urls): CheckoutSession
    {
        $payment->loadMissing(['booking.cars', 'user']);
        $booking = $payment->booking;

        $response = $this->http()->asForm()->post('/v3/bills', [
            'collection_id' => $this->config['collection_id'],
            'email' => $payment->user->email,
            'name' => Str::limit($payment->user->name, 255, ''),
            'amount' => $payment->amountInMinorUnits(),
            'callback_url' => $urls['callback'],
            'redirect_url' => $urls['return'],
            'description' => Str::limit("EasyCar booking {$booking->reference}: {$booking->car_names}", 200, ''),
            'reference_1_label' => 'Booking',
            'reference_1' => $booking->reference,
            'reference_2_label' => 'Payment',
            'reference_2' => $payment->uuid,
        ]);

        if (! $response->successful() || ! $response->json('url')) {
            throw new PaymentException(
                'We could not start the FPX payment. Please try again or choose another method.',
                ['status' => $response->status(), 'error' => $response->json('error')]
            );
        }

        return new CheckoutSession($response->json('id'), $response->json('url'));
    }

    public function parseWebhook(Request $request): GatewayResult
    {
        $data = $request->request->all();

        if (! isset($data['x_signature'], $data['id'])) {
            throw new InvalidSignatureException('Missing Billplz signature.');
        }

        $this->assertSignature($data, (string) $data['x_signature']);

        $paid = filter_var($data['paid'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $state = (string) ($data['state'] ?? '');

        return new GatewayResult(
            eventId: 'bill:'.$data['id'].':'.$state.':'.($data['paid_at'] ?? ''),
            type: 'bill.'.($state ?: 'callback'),
            status: match (true) {
                $paid => PaymentStatus::Paid,
                $state === 'deleted' => PaymentStatus::Cancelled,
                default => null,
            },
            gatewayReference: (string) $data['id'],
            failureReason: $state === 'deleted' ? 'The bill was cancelled.' : null,
            payload: $data,
        );
    }

    public function verifyReturn(Request $request, Payment $payment): ?GatewayResult
    {
        $billplz = $request->query('billplz');

        if (! is_array($billplz) || ! isset($billplz['id'], $billplz['x_signature'])) {
            return null;
        }

        $fields = [];
        foreach (['id', 'paid', 'paid_at'] as $key) {
            $fields['billplz'.$key] = (string) ($billplz[$key] ?? '');
        }

        try {
            $this->assertSignature($fields, (string) $billplz['x_signature']);
        } catch (InvalidSignatureException) {
            return null;
        }

        if (! hash_equals((string) $payment->gateway_reference, (string) $billplz['id'])
            || ! filter_var($billplz['paid'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            return null;
        }

        return new GatewayResult(
            eventId: 'return:'.$billplz['id'].':paid',
            type: 'bill.return',
            status: PaymentStatus::Paid,
            paymentUuid: $payment->uuid,
            gatewayReference: (string) $billplz['id'],
            payload: $billplz,
        );
    }

    public function supportsRefunds(): bool
    {
        return false; // Billplz has no refund API — refunds are processed manually.
    }

    public function refund(Payment $payment, float $amount, string $reason): RefundResult
    {
        throw new PaymentException('Billplz does not support automatic refunds.');
    }

    /**
     * Exposed for tests and for anyone wiring up Billplz manually.
     */
    public function sign(array $data): string
    {
        $pieces = [];

        foreach ($data as $key => $value) {
            if ($key === 'x_signature') {
                continue;
            }
            $pieces[] = $key.(is_bool($value) ? ($value ? 'true' : 'false') : (string) $value);
        }

        sort($pieces, SORT_STRING | SORT_FLAG_CASE);

        return hash_hmac('sha256', implode('|', $pieces), (string) $this->config['x_signature']);
    }

    private function assertSignature(array $data, string $signature): void
    {
        if (! hash_equals($this->sign($data), $signature)) {
            throw new InvalidSignatureException('Billplz signature mismatch.');
        }
    }

    private function http(): PendingRequest
    {
        $base = ($this->config['sandbox'] ?? true)
            ? 'https://www.billplz-sandbox.com/api'
            : 'https://www.billplz.com/api';

        return Http::baseUrl($base)
            ->withBasicAuth((string) $this->config['api_key'], '')
            ->acceptJson()
            ->timeout(20)
            ->retry(1, 300, fn ($e) => $e instanceof ConnectionException, throw: false);
    }
}
