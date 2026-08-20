<?php

namespace App\Models;

use App\Notifications\BookingStatusChanged;
use App\Services\BookingAvailabilityService;
use App\Services\DashboardService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * SUPERSEDES the Booking.php from Level 2. Everything from Level 2 is
 * unchanged (TD-03/TD-04/SoftDeletes fixes, dashboard cache invalidation) —
 * the only addition is dispatching BookingStatusChanged from
 * approve()/reject(), which finally makes FR-NOT-02/FR-APR-06 real: those
 * two methods have had a commented-out "send notification" intent since
 * before this project's fixes began. Wired up once, here, so every caller
 * (Admin\BookingController, Staff\BookingController, and the Level 2
 * dashboard's PendingApprovalQueue) sends it automatically.
 *
 * FIX (TD-04, carried forward from Level 2): approve() deliberately calls
 * hasApprovedConflict() rather than the general-purpose hasConflict(). Two
 * competing *pending* bookings for the same car/dates must be allowed to
 * coexist — the whole point of this re-check is to stop a second one being
 * approved for a car that's already been committed to someone else. Every
 * kit since Level 1 kit 1 has shipped this method reverted back to
 * hasConflict(), which would make every pending booking block every other
 * pending booking's approval (including the very first one) — caught by
 * tests/Feature/BookingConflictTest.php's regression case each time.
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
        return (int) max(0, Carbon::now()->diffInDays($this->start_date, false));
    }

    public function canBeApproved(): bool
    {
        return $this->isPending() && $this->days_until_start >= 2;
    }

    public function canBeRejected(): bool
    {
        return $this->isPending();
    }

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

        $this->forgetDashboardCache();
        $this->user->notify(new BookingStatusChanged($this));

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

        $this->forgetDashboardCache();
        $this->user->notify(new BookingStatusChanged($this));

        return true;
    }

    /**
     * Invalidates the dashboard's cached KPIs for every branch this booking
     * touches, so an approval/rejection is reflected the moment it happens
     * rather than waiting out the cache TTL.
     */
    private function forgetDashboardCache(): void
    {
        $this->loadMissing('cars');

        $branchIds = $this->cars->pluck('branch_id')->unique();

        DashboardService::forgetCacheFor(null); // the "all branches" admin view

        foreach ($branchIds as $branchId) {
            DashboardService::forgetCacheFor($branchId);
        }
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
