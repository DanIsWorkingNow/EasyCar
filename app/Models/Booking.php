<?php

namespace App\Models;

use App\Services\BookingAvailabilityService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * FIX (TD-17): this model previously did not use SoftDeletes, even though a
 * migration adds a deleted_at column to bookings and multiple queries across
 * the codebase (whereNull('bookings.deleted_at')) assume soft-deleted rows
 * persist. Without the trait, BookingController::destroy()'s $booking->delete()
 * was a HARD delete — and because car_booking.booking_id cascades on delete,
 * cancelling a booking permanently destroyed the booking and its car_booking
 * rows with no record they ever existed. Adding SoftDeletes here fixes that:
 * cancellation is now recoverable and auditable.
 */
class Booking extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'start_date',
        'end_date',
        'total_days',
        'status',
        'approved_by',
        'approved_at',
        'approval_notes',
        'rejection_reason',
        'total_price',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function cars(): BelongsToMany
    {
        return $this->belongsToMany(Car::class, 'car_booking')
            ->withPivot('quantity', 'price')
            ->withTimestamps();
    }

    /**
     * FIX (TD-03): total_price is the single source of truth, computed once
     * and stored at booking creation/edit time. This replaces the three
     * separate totals that previously coexisted here and could disagree
     * (calculateTotalPrice(), getTotalCostAttribute(), calculateTotalCost()).
     */
    public function calculateTotalPrice(): float
    {
        return (float) $this->cars->sum(fn ($car) => $car->pivot->price ?? 0);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function getStatusBadgeAttribute(): string
    {
        $badges = [
            'pending' => '<span class="badge bg-warning text-dark">⏳ Pending</span>',
            'approved' => '<span class="badge bg-success">✅ Approved</span>',
            'rejected' => '<span class="badge bg-danger">❌ Rejected</span>',
            'completed' => '<span class="badge bg-primary">🎉 Completed</span>',
            'cancelled' => '<span class="badge bg-secondary">🚫 Cancelled</span>',
        ];

        return $badges[$this->status] ?? '<span class="badge bg-light text-dark">Unknown</span>';
    }

    public function getBookingPeriodAttribute(): string
    {
        return $this->start_date->format('M d, Y') . ' - ' . $this->end_date->format('M d, Y');
    }

    public function getDaysUntilStartAttribute(): int
    {
        return max(0, Carbon::now()->diffInDays($this->start_date, false));
    }

    public function canBeApproved(): bool
    {
        return $this->isPending() && $this->days_until_start >= 2;
    }

    public function canBeRejected(): bool
    {
        return $this->isPending();
    }

    /**
     * FIX (TD-04): every attached car's availability is now re-checked at
     * the moment of approval — not only at booking creation — closing the
     * race condition where two overlapping pending bookings for the same
     * car could both previously be approved. Because this check lives here
     * rather than in each caller, Admin\BookingController's and
     * Staff\BookingController's approve()/reject()/bulkApprove() all get
     * the fix automatically.
     */
    public function approve(User $admin, ?string $notes = null): bool
    {
        if (! $this->canBeApproved()) {
            return false;
        }

        $availability = app(BookingAvailabilityService::class);

        foreach ($this->cars as $car) {
            $conflict = $availability->hasApprovedConflict(
                $car->id,
                $this->start_date->toDateString(),
                $this->end_date->toDateString(),
                $this->id
            );

            if ($conflict) {
                return false;
            }
        }

        $this->update([
            'status' => 'approved',
            'approved_by' => $admin->id,
            'approved_at' => now(),
            'approval_notes' => $notes,
        ]);

        return true;
    }

    public function reject(User $admin, string $reason): bool
    {
        if (! $this->canBeRejected()) {
            return false;
        }

        $this->update([
            'status' => 'rejected',
            'approved_by' => $admin->id,
            'approved_at' => now(),
            'rejection_reason' => $reason,
        ]);

        return true;
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
}
