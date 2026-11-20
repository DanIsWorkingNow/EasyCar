<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PromoCode extends Model
{
    public const PERCENT = 'percent';

    public const FIXED = 'fixed';

    protected $fillable = [
        'code', 'description', 'type', 'value', 'max_discount', 'min_spend', 'starts_at', 'ends_at',
        'max_redemptions', 'per_user_limit', 'first_booking_only', 'is_active',
    ];

    protected $casts = [
        'value' => 'float',
        'max_discount' => 'float',
        'min_spend' => 'float',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'max_redemptions' => 'integer',
        'per_user_limit' => 'integer',
        'first_booking_only' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(fn (PromoCode $p) => $p->code = strtoupper(trim($p->code)));
    }

    /** @return HasMany<Booking, $this> */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /** Redemptions = bookings using the code that still stand (cancelled/rejected free it up). */
    public function redemptionCount(?int $userId = null): int
    {
        return $this->bookings()
            ->whereNotIn('status', Booking::RELEASED_STATUSES)
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->count();
    }

    public function label(): string
    {
        return $this->type === self::PERCENT
            ? rtrim(rtrim(number_format($this->value, 2), '0'), '.').'% off'.($this->max_discount ? ' (up to RM '.number_format($this->max_discount, 0).')' : '')
            : 'RM '.number_format($this->value, 2).' off';
    }

    public function discountFor(float $subtotal): float
    {
        $discount = $this->type === self::PERCENT ? $subtotal * $this->value / 100 : $this->value;

        if ($this->max_discount) {
            $discount = min($discount, $this->max_discount);
        }

        return round(max(0, min($discount, $subtotal)), 2);
    }
}
