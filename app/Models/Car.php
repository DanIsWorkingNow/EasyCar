<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * SUPERSEDES the Car.php from Level 1 Part 2 (HasFactory trait,
 * price_per_day/photo fillable). This version adds the FR-CAR-04 status
 * field and a small helper for tracking how long a car has been in its
 * current status (downtime).
 */
class Car extends Model
{
    use HasFactory;

    protected $fillable = ['brand', 'model', 'type', 'transmission', 'branch_id', 'price_per_day', 'photo', 'status'];

    protected $casts = [
        'price_per_day' => 'decimal:2',
        'status_changed_at' => 'datetime',
    ];

    public function bookings()
    {
        return $this->belongsToMany(Booking::class, 'car_booking')
            ->withPivot('rental_start', 'rental_end', 'quantity', 'price')
            ->withTimestamps();
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function scopeUnderMaintenance($query)
    {
        return $query->where('status', 'maintenance');
    }

    /**
     * Change status and stamp when it changed, so downtime (time spent in
     * 'maintenance') can be measured later from status_changed_at.
     */
    public function setStatus(string $status): void
    {
        $this->update([
            'status' => $status,
            'status_changed_at' => now(),
        ]);
    }

    public function getDaysInCurrentStatusAttribute(): int
    {
        return $this->status_changed_at ? $this->status_changed_at->diffInDays(now()) : 0;
    }
}
