<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingAddon extends Model
{
    protected $fillable = ['booking_id', 'addon_id', 'name', 'pricing', 'quantity', 'unit_price', 'amount'];

    protected $casts = ['quantity' => 'integer', 'unit_price' => 'float', 'amount' => 'float'];

    /** @return BelongsTo<Addon, $this> */
    public function addon(): BelongsTo
    {
        return $this->belongsTo(Addon::class);
    }
}
