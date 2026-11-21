<?php

namespace App\Models;

use App\Enums\BookingPaymentStatus;
use App\Enums\PaymentStatus;
use App\Notifications\BookingStatusChanged;
use App\Services\BookingAvailabilityService;
use App\Services\DashboardService;
use App\Services\PaymentService;
use App\Services\Pricing\LoyaltyService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Booking lifecycle: pending -> approved | rejected | cancelled -> completed.
 *
 * TD-04 (carried forward): approve() deliberately calls
 * hasApprovedConflict() rather than hasConflict(). Two competing *pending*
 * bookings for the same car/dates must be allowed to coexist — the re-check
 * only stops a second one being approved for a car already committed to
 * someone else. Guarded by tests/Feature/BookingConflictTest.php.
 *
 * Payments: a booking can be paid while pending or approved. Rejecting or
 * cancelling a paid booking triggers a refund through PaymentService, which
 * either refunds through the gateway or flags it for manual processing when
 * the gateway has no refund API.
 */
class Booking extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUSES = ['pending', 'approved', 'rejected', 'completed', 'cancelled'];

    /** Customer-facing label and badge tone per status. */
    private const STATUS_META = [
        'pending' => ['label' => 'Pending approval', 'tone' => 'warning'],
        'approved' => ['label' => 'Confirmed', 'tone' => 'success'],
        'rejected' => ['label' => 'Rejected', 'tone' => 'danger'],
        'completed' => ['label' => 'Completed', 'tone' => 'info'],
        'cancelled' => ['label' => 'Cancelled', 'tone' => 'neutral'],
    ];

    /** Statuses that no longer hold a car (excluded from availability checks). */
    public const RELEASED_STATUSES = ['rejected', 'cancelled'];

    protected $fillable = [
        'user_id',
        'reference',
        'start_date',
        'end_date',
        'total_days',
        'status',
        'approved_by',
        'approved_at',
        'approval_notes',
        'rejection_reason',
        'total_price',
        'payment_status',
        'cancelled_at',
        'cancellation_reason',
        'picked_up_at',
        'returned_at',
        'completed_at',
        'overdue_notified_at',
        'terms_version',
        'terms_accepted_at',
        'terms_accepted_ip',
        'promo_code_id',
        'discount_amount',
        'addons_amount',
        'points_redeemed',
        'points_discount',
        'deposit_amount',
    ];

    protected $attributes = [
        'payment_status' => 'unpaid',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'returned_at' => 'datetime',
        'completed_at' => 'datetime',
        'overdue_notified_at' => 'datetime',
        'terms_accepted_at' => 'datetime',
        'total_price' => 'float',
        'discount_amount' => 'float',
        'addons_amount' => 'float',
        'points_redeemed' => 'integer',
        'points_discount' => 'float',
        'deposit_amount' => 'float',
        'payment_status' => BookingPaymentStatus::class,
    ];

    protected static function booted(): void
    {
        // Rewards follow the booking's status, whichever path changed it
        // (staff, customer, the unpaid-hold expiry job or the API).
        static::updated(function (Booking $booking) {
            if (! $booking->wasChanged('status')) {
                return;
            }
            if ($booking->status === 'completed') {
                app(LoyaltyService::class)->earnFor($booking);
            } elseif (in_array($booking->status, self::RELEASED_STATUSES, true)) {
                app(LoyaltyService::class)->reverseRedemption($booking);
            }
        });

        static::creating(function (Booking $booking) {
            $booking->reference ??= static::generateReference();
        });
    }

    /**
     * Short, unambiguous, customer-facing code (no 0/O/1/I).
     */
    public static function generateReference(): string
    {
        $alphabet = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

        do {
            $code = 'EC-';
            for ($i = 0; $i < 8; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
        } while (static::withTrashed()->where('reference', $code)->exists());

        return $code;
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<User, $this> */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /** @return BelongsToMany<Car, $this> */
    public function cars(): BelongsToMany
    {
        return $this->belongsToMany(Car::class, 'car_booking')
            ->withPivot('quantity', 'price')
            ->withTimestamps();
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** @return HasOne<Payment, $this> */
    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    /** @return HasOne<Payment, $this> */
    public function successfulPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->ofMany(['id' => 'max'], function (Builder $q) {
            $q->whereIn('status', [PaymentStatus::Paid->value, PaymentStatus::Refunded->value, PaymentStatus::RefundPending->value]);
        });
    }

    /** @return HasMany<VehicleInspection, $this> */
    public function inspections(): HasMany
    {
        return $this->hasMany(VehicleInspection::class);
    }

    /** @return HasMany<BookingAddon, $this> */
    public function addons(): HasMany
    {
        return $this->hasMany(BookingAddon::class);
    }

    /** @return BelongsTo<PromoCode, $this> */
    public function promoCode(): BelongsTo
    {
        return $this->belongsTo(PromoCode::class);
    }

    /** @return HasMany<Review, $this> */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /** Car hire only, before add-ons, promo discount and points. */
    public function rentalAmount(): float
    {
        return round((float) $this->total_price - (float) $this->addons_amount + (float) $this->discount_amount + (float) $this->points_discount, 2);
    }

    /** @return HasOne<BookingDeposit, $this> */
    public function deposit(): HasOne
    {
        return $this->hasOne(BookingDeposit::class);
    }

    /** @return HasMany<LoyaltyTransaction, $this> */
    public function loyaltyTransactions(): HasMany
    {
        return $this->hasMany(LoyaltyTransaction::class);
    }

    /** @return HasMany<BookingCharge, $this> */
    public function charges(): HasMany
    {
        return $this->hasMany(BookingCharge::class);
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

    public function isPaid(): bool
    {
        return $this->payment_status === BookingPaymentStatus::Paid;
    }

    /**
     * @return array{label: string, tone: string}
     */
    public function statusMeta(): array
    {
        $status = (string) $this->getAttribute('status');

        return self::STATUS_META[$status] ?? ['label' => ucfirst($status), 'tone' => 'neutral'];
    }

    /**
     * Kept for backwards compatibility with any view still echoing it.
     */
    public function getStatusBadgeAttribute(): string
    {
        $meta = $this->statusMeta();

        return '<span class="badge badge-'.e($meta['tone']).'">'.e($meta['label']).'</span>';
    }

    public function getBookingPeriodAttribute(): string
    {
        return $this->start_date->format('M d, Y').' - '.$this->end_date->format('M d, Y');
    }

    public function getDaysUntilStartAttribute(): int
    {
        return (int) max(0, Carbon::now()->startOfDay()->diffInDays($this->start_date, false));
    }

    public function getCarNamesAttribute(): string
    {
        return $this->cars->map(fn ($c) => "{$c->brand} {$c->model}")->implode(', ');
    }

    // ── Trip lifecycle (Phase 2: rental operations) ──────────────────

    /** When the car is handed over: start date at the configured pickup time. */
    public function pickupDueAt(): Carbon
    {
        return $this->start_date->copy()->setTimeFromTimeString((string) config('rentals.pickup_time', '10:00'));
    }

    /** When the car is due back: end date at the configured return time. */
    public function returnDueAt(): Carbon
    {
        return $this->end_date->copy()->setTimeFromTimeString((string) config('rentals.return_time', '18:00'));
    }

    public function isOnTrip(): bool
    {
        return $this->isApproved() && $this->picked_up_at !== null && $this->completed_at === null;
    }

    public function isOverdue(): bool
    {
        return $this->isOnTrip()
            && now()->greaterThan($this->returnDueAt()->addMinutes((int) config('rentals.late_grace_minutes', 60)));
    }

    /**
     * Where the trip is, independent of the approval status.
     *
     * @return array{key: string, label: string, tone: string}|null
     */
    public function tripMeta(): ?array
    {
        return match (true) {
            $this->isCompleted() => ['key' => 'completed', 'label' => 'Trip completed', 'tone' => 'info'],
            $this->isOverdue() => ['key' => 'overdue', 'label' => 'Return overdue', 'tone' => 'danger'],
            $this->isOnTrip() => ['key' => 'on_trip', 'label' => 'On trip', 'tone' => 'success'],
            $this->isApproved() => ['key' => 'awaiting_pickup', 'label' => 'Awaiting pick-up', 'tone' => 'warning'],
            default => null,
        };
    }

    public function inspectionFor(int $carId, string $type): ?VehicleInspection
    {
        return $this->inspections->first(fn (VehicleInspection $i) => $i->car_id === $carId && $i->type === $type);
    }

    /** Sum of extra charges that are neither paid nor waived. */
    public function outstandingCharges(): float
    {
        return round((float) $this->charges
            ->filter(fn (BookingCharge $c) => ! $c->isPaid() && ! $c->isVoided())
            ->sum('amount'), 2);
    }

    public function canPayCharges(): bool
    {
        return (bool) config('payments.enabled') && $this->outstandingCharges() > 0;
    }

    public function canBeApproved(): bool
    {
        return $this->approvalBlocker() === null;
    }

    /** Why this booking can't be approved yet, or null if it can. */
    public function approvalBlocker(): ?string
    {
        return match (true) {
            ! $this->isPending() => 'Only pending bookings can be approved.',
            $this->days_until_start < 2 => 'Bookings need at least 2 days\' notice to be approved.',
            default => $this->driverBlocker(),
        };
    }

    /**
     * Driver-eligibility check (Phase 3): verified licence and ID, licence
     * valid until the return date, and minimum age at pick-up.
     */
    public function driverBlocker(): ?string
    {
        if (! config('compliance.require_verified_driver')) {
            return null;
        }

        $profile = $this->user?->driverProfile;
        $minAge = (int) config('compliance.min_driver_age', 21);

        return match (true) {
            $profile === null => 'The customer hasn\'t submitted their driving licence yet.',
            ! $profile->isVerified() => 'The customer\'s driving licence is awaiting verification.',
            $profile->licence_expiry->lt($this->end_date) => 'The customer\'s licence expires ('.$profile->licence_expiry->format('d M Y').') before the return date.',
            $profile->ageOn($this->start_date) < $minAge => "The driver must be at least {$minAge} at pick-up.",
            default => null,
        };
    }

    public function canBeRejected(): bool
    {
        return $this->isPending();
    }

    public function canBeEditedBy(User $user): bool
    {
        return $user->id === $this->user_id
            && $this->isPending()
            && ! $this->isPaid()
            && $this->start_date->isFuture();
    }

    public function canBeCancelledBy(User $user): bool
    {
        return $user->id === $this->user_id
            && in_array($this->status, ['pending', 'approved'], true)
            && $this->picked_up_at === null
            && $this->start_date->isFuture();
    }

    public function canBePaid(): bool
    {
        return config('payments.enabled')
            && in_array($this->status, ['pending', 'approved'], true)
            && $this->payment_status === BookingPaymentStatus::Unpaid
            && $this->total_price > 0
            && ! $this->end_date->isPast();
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

        $this->refundIfPaid('Booking rejected: '.$reason);
        $this->forgetDashboardCache();
        $this->user->notify(new BookingStatusChanged($this));

        return true;
    }

    public function cancel(?User $by = null, ?string $reason = null): bool
    {
        if (! in_array($this->status, ['pending', 'approved'], true)) {
            return false;
        }

        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason ?: match (true) {
                $by === null => 'Cancelled automatically',
                $by->id === $this->user_id => 'Cancelled by customer',
                default => 'Cancelled by EasyCar staff',
            },
        ]);

        // Abandon any checkout still in flight so a late gateway callback
        // can't mark a cancelled booking as paid without a refund.
        $this->payments()->where('status', PaymentStatus::Pending->value)->update([
            'status' => PaymentStatus::Cancelled->value,
            'failure_reason' => 'Booking cancelled',
        ]);

        $this->refundIfPaid($this->cancellation_reason);
        $this->forgetDashboardCache();

        if ($by === null || $by->id !== $this->user_id) {
            $this->user->notify(new BookingStatusChanged($this));
        }

        return true;
    }

    private function refundIfPaid(string $reason): void
    {
        if (! $this->isPaid()) {
            return;
        }

        try {
            app(PaymentService::class)->refundBooking($this, $reason);
            // Pick up the payment_status PaymentService just wrote so the
            // notification below reflects the refund.
            $this->payment_status = static::withTrashed()->whereKey($this->id)->value('payment_status');
            $this->syncOriginalAttribute('payment_status');
        } catch (\Throwable $e) {
            // Never block a rejection/cancellation on a gateway outage —
            // PaymentService has already flagged the payment for manual
            // follow-up; log loudly so finance can act on it.
            Log::error('Automatic refund failed', ['booking' => $this->id, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Invalidates the dashboard's cached KPIs for every branch this booking
     * touches, so an approval/rejection is reflected immediately.
     */
    private function forgetDashboardCache(): void
    {
        $this->loadMissing('cars');

        DashboardService::forgetCacheFor(null);

        foreach ($this->cars->pluck('branch_id')->unique() as $branchId) {
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

    public function scopeUpcoming($query)
    {
        return $query->whereIn('status', ['pending', 'approved'])
            ->whereDate('end_date', '>=', now()->toDateString());
    }

    public function scopeSearch($query, ?string $term)
    {
        if (! filled($term)) {
            return $query;
        }

        $term = Str::lower(trim($term));

        return $query->where(function ($q) use ($term) {
            $q->whereRaw('LOWER(reference) LIKE ?', ["%{$term}%"])
                ->orWhereHas('user', function ($u) use ($term) {
                    $u->whereRaw('LOWER(name) LIKE ?', ["%{$term}%"])
                        ->orWhereRaw('LOWER(email) LIKE ?', ["%{$term}%"]);
                });
        });
    }
}
