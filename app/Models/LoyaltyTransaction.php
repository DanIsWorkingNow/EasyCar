<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One line in a customer's points ledger. Balance = sum(points). */
class LoyaltyTransaction extends Model
{
    public const EARN = 'earn';

    public const REDEEM = 'redeem';

    public const REVERSAL = 'reversal';

    public const ADJUSTMENT = 'adjustment';

    protected $fillable = ['user_id', 'booking_id', 'points', 'type', 'description', 'created_by'];

    protected $casts = ['points' => 'integer'];

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
}
