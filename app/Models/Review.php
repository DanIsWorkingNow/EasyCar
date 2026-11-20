<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A verified review: only possible for a car on a completed booking the
 * reviewer actually rented (see App\Services\ReviewService).
 */
class Review extends Model
{
    protected $fillable = ['booking_id', 'car_id', 'user_id', 'rating', 'title', 'body', 'is_published', 'reply', 'replied_by', 'replied_at'];

    protected $casts = [
        'rating' => 'integer',
        'is_published' => 'boolean',
        'replied_at' => 'datetime',
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

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @param Builder<Review> $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /** "Aisha R." — first name and initial only, for privacy. */
    public function reviewerName(): string
    {
        $parts = preg_split('/\s+/', trim((string) $this->user?->name)) ?: [];

        return trim(($parts[0] ?? 'Guest').' '.(isset($parts[1]) ? mb_substr($parts[1], 0, 1).'.' : ''));
    }
}
