<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Carbon\Carbon;

class Booking extends Model
{
    use HasFactory;

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
        'approved_at' => 'datetime'
    ];

    /**
     * Get the user who made the booking
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin who approved/rejected the booking
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the cars associated with this booking
     */
    public function cars(): BelongsToMany
{
    return $this->belongsToMany(Car::class, 'car_booking') // your pivot table name
                ->withPivot('quantity', 'price')
                ->withTimestamps();
}

public function calculateTotalPrice()
{
    $total = 0;
    foreach ($this->cars as $car) {
        $total += $car->pivot->price;
    }
    return $total;
}




    /**
     * Status helper methods
     */
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

    /**
     * Get status badge HTML for display
     */
    public function getStatusBadgeAttribute(): string
    {
        $badges = [
            'pending' => '<span class="badge bg-warning text-dark">⏳ Pending</span>',
            'approved' => '<span class="badge bg-success">✅ Approved</span>',
            'rejected' => '<span class="badge bg-danger">❌ Rejected</span>',
            'completed' => '<span class="badge bg-primary">🎉 Completed</span>',
            'cancelled' => '<span class="badge bg-secondary">🚫 Cancelled</span>'
        ];

        return $badges[$this->status] ?? '<span class="badge bg-light text-dark">Unknown</span>';
    }

    /**
     * Get formatted booking period
     */
    public function getBookingPeriodAttribute(): string
    {
        return $this->start_date->format('M d, Y') . ' - ' . $this->end_date->format('M d, Y');
    }

    /**
     * Get days until booking starts
     */
    public function getDaysUntilStartAttribute(): int
    {
        return max(0, Carbon::now()->diffInDays($this->start_date, false));
    }

    /**
     * Check if booking can be approved
     */
    public function canBeApproved(): bool
    {
        return $this->isPending() && $this->days_until_start >= 2;
    }

    /**
     * Check if booking can be rejected
     */
    public function canBeRejected(): bool
    {
        return $this->isPending();
    }

    /**
     * Approve the booking
     */
    public function approve(User $admin, string $notes = null): bool
    {
        if (!$this->canBeApproved()) {
            return false;
        }

        $this->update([
            'status' => 'approved',
            'approved_by' => $admin->id,
            'approved_at' => now(),
            'approval_notes' => $notes
        ]);

        return true;
    }

    /**
     * Reject the booking
     */
    public function reject(User $admin, string $reason): bool
    {
        if (!$this->canBeRejected()) {
            return false;
        }

        $this->update([
            'status' => 'rejected',
            'approved_by' => $admin->id,
            'approved_at' => now(),
            'rejection_reason' => $reason
        ]);

        return true;
    }

    /**
     * Get total booking cost (you'll need to implement this based on your pricing logic)
     */
    public function getTotalCostAttribute(): float
{
    $totalCost = 0;
    foreach ($this->cars as $car) {
        // Use the price stored in the pivot table for this car in the booking
        $totalCost += $car->pivot->price ?? 0;
    }
    return $totalCost;
}

public function calculateTotalCost(): float
{
    return $this->cars->sum(function($car) {
        return $car->pivot->price ?? 0;
    });
}




    /**
     * Scope for filtering bookings by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for pending bookings
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for approved bookings
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }




}