<?php

namespace App\Payments\Data;

use App\Enums\PaymentStatus;

/**
 * A verified statement from a gateway about one payment.
 *
 * $paymentUuid is our own Payment::$uuid when the gateway echoes it back
 * (metadata / external reference); otherwise the payment is looked up by
 * $gatewayReference.
 */
final readonly class GatewayResult
{
    public function __construct(
        public string $eventId,
        public string $type,
        public ?PaymentStatus $status,
        public ?string $paymentUuid = null,
        public ?string $gatewayReference = null,
        public ?string $failureReason = null,
        public array $meta = [],
        public array $payload = [],
    ) {}

    /** A notification we log but that doesn't change payment state. */
    public function isInformational(): bool
    {
        return $this->status === null;
    }
}
