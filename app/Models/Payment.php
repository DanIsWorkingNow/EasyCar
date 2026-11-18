<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * One checkout attempt for a booking. A booking may accumulate several
 * (abandoned card checkout, then a successful FPX one); at most one ends
 * up Paid. Routed by uuid so payment URLs never expose sequential ids.
 */
class Payment extends Model
{
    protected $fillable = [
        'uuid', 'booking_id', 'user_id', 'gateway', 'gateway_reference', 'amount',
        'refunded_amount', 'currency', 'status', 'checkout_url', 'failure_reason',
        'meta', 'paid_at', 'refunded_at',
    ];

    protected $casts = [
        'amount' => 'float',
        'refunded_amount' => 'float',
        'status' => PaymentStatus::class,
        'meta' => 'array',
        'paid_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Payment $payment) {
            $payment->uuid ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /** @return BelongsTo<Booking, $this> */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class)->withTrashed();
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<PaymentEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(PaymentEvent::class);
    }

    /** Amount in the smallest currency unit (sen), as gateways expect. */
    public function amountInMinorUnits(): int
    {
        return (int) round($this->amount * 100);
    }

    public function isPaid(): bool
    {
        return $this->status === PaymentStatus::Paid;
    }

    public function isPending(): bool
    {
        return $this->status === PaymentStatus::Pending;
    }

    public function gatewayLabel(): string
    {
        return config("payments.gateways.{$this->gateway}.label", Str::headline($this->gateway));
    }

    public function mergeMeta(array $values): void
    {
        $this->meta = array_merge($this->meta ?? [], $values);
    }
}
