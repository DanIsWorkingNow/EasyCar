<?php

namespace App\Payments\Gateways;

use App\Models\Payment;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\Data\CheckoutSession;
use App\Payments\Data\GatewayResult;
use App\Payments\Data\RefundResult;
use App\Payments\Exceptions\InvalidSignatureException;
use App\Payments\Exceptions\PaymentException;
use Illuminate\Http\Request;

/**
 * Money taken in person at a branch (cash, card terminal, bank transfer),
 * recorded by staff through PaymentService::recordCounterPayment(). It is
 * never offered at online checkout and has no webhook; refunds are paid
 * back at the counter and confirmed by an admin.
 */
class CounterGateway implements PaymentGateway
{
    public const METHODS = [
        'cash' => 'Cash',
        'card_terminal' => 'Card terminal',
        'bank_transfer' => 'Bank transfer / DuitNow',
    ];

    public function name(): string
    {
        return 'counter';
    }

    public function isConfigured(): bool
    {
        return true;
    }

    public function createCheckout(Payment $payment, array $urls): CheckoutSession
    {
        throw new PaymentException('Counter payments are recorded by branch staff.');
    }

    public function parseWebhook(Request $request): GatewayResult
    {
        throw new InvalidSignatureException('The counter gateway does not accept webhooks.');
    }

    public function verifyReturn(Request $request, Payment $payment): ?GatewayResult
    {
        return null;
    }

    public function supportsRefunds(): bool
    {
        return false;
    }

    public function refund(Payment $payment, float $amount, string $reason): RefundResult
    {
        throw new PaymentException('Counter payments are refunded in person.');
    }
}
