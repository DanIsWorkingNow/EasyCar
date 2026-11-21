<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Car extends Model
{
    use HasFactory;

    public const TYPES = ['Sedan', 'SUV', 'Hatchback', 'MPV'];

    public const TRANSMISSIONS = ['Automatic', 'Manual'];

    public const FUEL_TYPES = ['Petrol', 'Diesel', 'Hybrid', 'Electric'];

    /** Fallback paint colours for the 3D viewer when none is set. */
    public const PAINT_PALETTE = ['#b91c1c', '#e5e7eb', '#1f2937', '#1d4ed8', '#9ca3af', '#0f766e', '#7c2d12', '#111827'];

    protected $fillable = [
        'brand', 'model', 'year', 'type', 'transmission', 'seats', 'fuel_type',
        'color', 'description', 'branch_id', 'price_per_day', 'deposit_amount', 'photo', 'model_3d',
        'is_featured', 'status', 'plate_number',
    ];

    protected $casts = [
        'price_per_day' => 'decimal:2',
        'status_changed_at' => 'datetime',
        'is_featured' => 'boolean',
        'seats' => 'integer',
        'year' => 'integer',
    ];

    /** @return BelongsToMany<Booking, $this> */
    public function bookings(): BelongsToMany
    {
        return $this->belongsToMany(Booking::class, 'car_booking')
            ->withPivot('rental_start', 'rental_end', 'quantity', 'price')
            ->withTimestamps();
    }

    /** Refundable security deposit for this car (own value, else by type, else default). */
    public function depositAmount(): float
    {
        return round((float) ($this->deposit_amount ?? config('pricing.deposit.by_type.'.$this->type) ?? config('pricing.deposit.default')), 2);
    }

    /** @return HasMany<Review, $this> */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Adds rating_avg / rating_count (published reviews) to a car query.
     *
     * @param  Builder<Car>  $query
     */
    public function scopeWithRatings($query): void
    {
        $query->withAvg(['reviews as rating_avg' => fn ($q) => $q->where('is_published', true)], 'rating')
            ->withCount(['reviews as rating_count' => fn ($q) => $q->where('is_published', true)]);
    }

    /** @return BelongsTo<Branch, $this> */
    public function branch(): BelongsTo
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

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeSearch($query, ?string $term)
    {
        if (! filled($term)) {
            return $query;
        }

        // Portable case-insensitive match (TSD DB-03).
        $term = Str::lower(trim($term));

        return $query->where(function ($q) use ($term) {
            $q->whereRaw('LOWER(brand) LIKE ?', ["%{$term}%"])
                ->orWhereRaw('LOWER(model) LIKE ?', ["%{$term}%"])
                ->orWhereRaw('LOWER(type) LIKE ?', ["%{$term}%"]);
        });
    }

    /** "SUV" -> "SUVs" (Str::plural would give "SUVS"). */
    public static function pluralType(?string $type): string
    {
        return match ($type) {
            'SUV', 'MPV' => $type.'s',
            default => Str::plural((string) $type),
        };
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

    public function getNameAttribute(): string
    {
        return trim("{$this->brand} {$this->model}");
    }

    /**
     * Resolves a displayable photo URL. Prefers an admin-uploaded `photo`
     * (public disk), falling back to the static seed images in
     * public/images/cars/ named after the model in lowercase with spaces
     * replaced by underscores (e.g. "X-Trail" -> x-trail.jpeg). Returns null
     * when neither exists so views never render a broken <img>.
     */
    public function getPhotoUrlAttribute(): ?string
    {
        if ($this->photo) {
            return Storage::url($this->photo);
        }

        $slug = strtolower(str_replace(' ', '_', (string) $this->model));
        $relativePath = "images/cars/{$slug}.jpeg";

        return file_exists(public_path($relativePath)) ? asset($relativePath) : null;
    }

    public function getModel3dUrlAttribute(): ?string
    {
        return $this->model_3d ? Storage::url($this->model_3d) : null;
    }

    /**
     * Paint colour for the 3D viewer — the stored colour, or a stable
     * palette pick so the same car always renders the same way.
     */
    public function getPaintColorAttribute(): string
    {
        if ($this->color && preg_match('/^#[0-9a-fA-F]{6}$/', $this->color)) {
            return strtolower($this->color);
        }

        return self::PAINT_PALETTE[($this->id ?? 0) % count(self::PAINT_PALETTE)];
    }

    /** Body style key the procedural 3D model understands. */
    public function getBodyStyleAttribute(): string
    {
        return match (Str::lower((string) $this->type)) {
            'suv' => 'suv',
            'mpv' => 'mpv',
            'hatchback' => 'hatchback',
            default => 'sedan',
        };
    }

    /**
     * Payload consumed by resources/js/car-viewer.
     *
     * @return array<string, mixed>
     */
    public function viewerConfig(): array
    {
        return [
            'body' => $this->body_style,
            'color' => $this->paint_color,
            'model' => $this->model_3d_url,
            'name' => $this->name,
        ];
    }
}
