<?php

namespace App\Payments\Contracts;

use App\Models\Payment;
use App\Payments\Data\CheckoutSession;
use App\Payments\Data\GatewayResult;
use App\Payments\Data\RefundResult;
use App\Payments\Exceptions\InvalidSignatureException;
use App\Payments\Exceptions\PaymentException;
use Illuminate\Http\Request;

interface PaymentGateway
{
    /** Driver key, e.g. "stripe". */
    public function name(): string;

    /** Whether the credentials this driver needs are present. */
    public function isConfigured(): bool;

    /**
     * Create a hosted checkout and return where to send the customer.
     *
     * @param  array{return: string, cancel: string, callback: string}  $urls
     *
     * @throws PaymentException
     */
    public function createCheckout(Payment $payment, array $urls): CheckoutSession;

    /**
     * Verify and interpret a server-to-server notification.
     *
     * @throws InvalidSignatureException when the request can't be authenticated
     */
    public function parseWebhook(Request $request): GatewayResult;

    /**
     * Interpret the customer's browser redirect back from the gateway.
     * Return null when the redirect can't be trusted on its own (the
     * webhook remains authoritative); never trust unsigned query strings.
     */
    public function verifyReturn(Request $request, Payment $payment): ?GatewayResult;

    public function supportsRefunds(): bool;

    /**
     * @throws PaymentException
     */
    public function refund(Payment $payment, float $amount, string $reason): RefundResult;
}
