<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Observers\PaymentObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * One checkout attempt for a booking. A booking may accumulate several
 * (abandoned card checkout, then a successful FPX one); at most one ends
 * up Paid. Routed by uuid so payment URLs never expose sequential ids.
 */
#[ObservedBy(PaymentObserver::class)]
class Payment extends Model
{
    /** The rental itself. */
    public const PURPOSE_BOOKING = 'booking';

    /** Extras billed after the trip (late return, fuel, damage…). */
    public const PURPOSE_CHARGES = 'charges';

    protected $fillable = [
        'uuid', 'booking_id', 'user_id', 'purpose', 'gateway', 'gateway_reference', 'amount',
        'refunded_amount', 'currency', 'status', 'checkout_url', 'failure_reason',
        'meta', 'paid_at', 'refunded_at',
    ];

    protected $attributes = [
        'purpose' => self::PURPOSE_BOOKING,
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

    /** @return HasMany<BookingCharge, $this> */
    public function charges(): HasMany
    {
        return $this->hasMany(BookingCharge::class);
    }

    /** @return HasMany<Invoice, $this> */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function invoice(): ?Invoice
    {
        return $this->invoices->firstWhere('kind', Invoice::INVOICE);
    }

    public function isForCharges(): bool
    {
        return $this->purpose === self::PURPOSE_CHARGES;
    }

    public function purposeLabel(): string
    {
        return $this->isForCharges() ? 'Extra charges' : 'Rental';
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
