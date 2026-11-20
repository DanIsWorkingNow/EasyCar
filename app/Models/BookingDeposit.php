<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingDeposit extends Model
{
    public const HELD = 'held';

    public const RELEASED = 'released';

    public const APPLIED = 'applied';

    protected $fillable = ['booking_id', 'amount', 'method', 'reference', 'status', 'applied_amount', 'collected_by', 'collected_at', 'settled_by', 'settled_at', 'notes'];

    protected $casts = [
        'amount' => 'float',
        'applied_amount' => 'float',
        'collected_at' => 'datetime',
        'settled_at' => 'datetime',
    ];

    /** @return BelongsTo<Booking, $this> */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class)->withTrashed();
    }

    /** @return BelongsTo<User, $this> */
    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    /** @return BelongsTo<User, $this> */
    public function settler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'settled_by');
    }

    public function isHeld(): bool
    {
        return $this->status === self::HELD;
    }

    public function methodLabel(): string
    {
        return config('pricing.deposit.methods')[$this->method] ?? ucfirst(str_replace('_', ' ', $this->method));
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::HELD => 'Held',
            self::RELEASED => 'Released',
            self::APPLIED => $this->applied_amount < $this->amount ? 'Part applied, rest released' : 'Applied to charges',
            default => ucfirst($this->status),
        };
    }

    public function releasedAmount(): float
    {
        return $this->status === self::HELD ? 0.0 : round($this->amount - $this->applied_amount, 2);
    }
}
