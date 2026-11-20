<?php

namespace App\Models;

use App\Enums\ChargeType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingCharge extends Model
{
    protected $fillable = [
        'booking_id', 'car_id', 'type', 'description', 'quantity', 'unit_amount', 'amount',
        'payment_id', 'paid_at', 'voided_at', 'void_reason', 'created_by',
    ];

    protected $casts = [
        'type' => ChargeType::class,
        'quantity' => 'float',
        'unit_amount' => 'float',
        'amount' => 'float',
        'paid_at' => 'datetime',
        'voided_at' => 'datetime',
    ];

    /** @return BelongsTo<Booking, $this> */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class)->withTrashed();
    }

    /** @return BelongsTo<Car, $this> */
    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
    }

    /** @return BelongsTo<Payment, $this> */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @param Builder<BookingCharge> $query */
    public function scopeActive(Builder $query): void
    {
        $query->whereNull('voided_at');
    }

    /** @param Builder<BookingCharge> $query */
    public function scopeOutstanding(Builder $query): void
    {
        $query->whereNull('voided_at')->whereNull('paid_at');
    }

    public function isPaid(): bool
    {
        return $this->paid_at !== null;
    }

    public function isVoided(): bool
    {
        return $this->voided_at !== null;
    }

    /** @return array{label: string, tone: string} */
    public function statusMeta(): array
    {
        return match (true) {
            $this->isVoided() => ['label' => 'Waived', 'tone' => 'neutral'],
            $this->isPaid() => ['label' => 'Paid', 'tone' => 'success'],
            default => ['label' => 'Due', 'tone' => 'warning'],
        };
    }
}
