<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';
    case RefundPending = 'refund_pending';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Awaiting payment',
            self::Paid => 'Paid',
            self::Failed => 'Failed',
            self::Cancelled => 'Cancelled',
            self::Refunded => 'Refunded',
            self::RefundPending => 'Refund pending',
        };
    }

    /** Badge tone used by <x-badge>. */
    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Paid => 'success',
            self::Failed => 'danger',
            self::Cancelled => 'neutral',
            self::Refunded => 'info',
            self::RefundPending => 'info',
        };
    }

    /**
     * Terminal states a gateway notification can no longer move a payment
     * out of (a late "failed" webhook must never downgrade a paid payment).
     */
    public function isSettled(): bool
    {
        return in_array($this, [self::Paid, self::Refunded, self::RefundPending], true);
    }
}
