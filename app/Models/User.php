<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name', 'email', 'password', 'role', 'userLevel', 'branch_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /** @return HasMany<Booking, $this> */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** @return BelongsTo<Branch, $this> */
    /** @return BelongsToMany<Car, $this> */
    public function favourites(): BelongsToMany
    {
        return $this->belongsToMany(Car::class, 'favourites')->withTimestamps();
    }

    /**
     * Favourite car ids, memoised for the request (every car tile asks).
     *
     * @return array<int, int>
     */
    public function favouriteCarIds(): array
    {
        return once(fn () => $this->favourites()->pluck('cars.id')->map(fn ($id) => (int) $id)->all());
    }

    /** @return HasOne<DriverProfile, $this> */
    public function driverProfile(): HasOne
    {
        return $this->hasOne(DriverProfile::class);
    }

    public function isVerifiedDriver(): bool
    {
        return (bool) $this->driverProfile?->isVerified();
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isStaff(): bool
    {
        return $this->hasRole('staff');
    }

    /** Route of the operations console this user may use, if any. */
    public function consoleRoute(): ?string
    {
        return match (true) {
            $this->isAdmin() => 'admin.dashboard',
            $this->isStaff() => 'staff.dashboard',
            default => null,
        };
    }

    public function getInitialsAttribute(): string
    {
        return collect(preg_split('/\s+/', trim((string) $this->name)))
            ->filter()
            ->take(2)
            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('') ?: '?';
    }
}
