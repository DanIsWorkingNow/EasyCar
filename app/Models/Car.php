<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * SUPERSEDES the Car.php shipped in Level 3 (which added HasFactory,
 * price_per_day/photo fillable, and the status field). This version adds
 * plate_number (FR-CAR-06) to $fillable — the column itself comes from this
 * kit's migration.
 */
class Car extends Model
{
    use HasFactory;

    protected $fillable = [
        'brand', 'model', 'type', 'transmission', 'branch_id',
        'price_per_day', 'photo', 'status', 'plate_number',
    ];

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

    /**
     * Resolves a displayable photo URL for this car. Prefers an
     * admin-uploaded `photo` (stored on the public disk via
     * admin/cars/{create,edit}). Falls back to the static seed images in
     * public/images/cars/, named after the car's model in lowercase with
     * spaces replaced by underscores (e.g. "X-Trail" -> x-trail.jpeg) —
     * this is the same convention the pre-Livewire bookings/create page
     * used, restored here after the new car-availability picker briefly
     * dropped it. Returns null (no broken <img>) if neither exists.
     */
    public function getPhotoUrlAttribute(): ?string
    {
        if ($this->photo) {
            return Storage::url($this->photo);
        }

        $slug = strtolower(str_replace(' ', '_', $this->model));
        $relativePath = "images/cars/{$slug}.jpeg";

        return file_exists(public_path($relativePath)) ? asset($relativePath) : null;
    }
}
