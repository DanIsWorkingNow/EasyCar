<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Addon extends Model
{
    public const PER_DAY = 'per_day';

    public const PER_BOOKING = 'per_booking';

    protected $fillable = ['name', 'description', 'icon', 'pricing', 'price', 'max_quantity', 'is_active', 'sort_order'];

    protected $casts = [
        'price' => 'float',
        'max_quantity' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /** @return HasMany<BookingAddon, $this> */
    public function bookingAddons(): HasMany
    {
        return $this->hasMany(BookingAddon::class);
    }

    /** @param Builder<Addon> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort_order')->orderBy('name');
    }

    public function amountFor(int $quantity, int $days): float
    {
        return round($this->price * $quantity * ($this->pricing === self::PER_DAY ? $days : 1), 2);
    }

    public function priceLabel(): string
    {
        return 'RM '.number_format($this->price, 2).($this->pricing === self::PER_DAY ? '/day' : ' per trip');
    }
}
