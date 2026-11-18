<?php

namespace App\Enums;

/**
 * Denormalised onto bookings.payment_status so listings can filter on it.
 * The payments table remains the source of truth.
 */
enum BookingPaymentStatus: string
{
    case Unpaid = 'unpaid';
    case Paid = 'paid';
    case RefundPending = 'refund_pending';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Unpaid',
            self::Paid => 'Paid',
            self::RefundPending => 'Refund pending',
            self::Refunded => 'Refunded',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Unpaid => 'warning',
            self::Paid => 'success',
            self::RefundPending, self::Refunded => 'info',
        };
    }
}
