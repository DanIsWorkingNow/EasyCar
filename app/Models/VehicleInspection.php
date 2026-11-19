<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A pickup or return handover for one car on one booking.
 *
 * Damage is recorded per zone of a top-down car diagram, so a return
 * inspection can be compared zone-by-zone with the pickup to show only
 * what is new.
 */
class VehicleInspection extends Model
{
    public const PICKUP = 'pickup';

    public const RETURN = 'return';

    /** Zones on the handover diagram (resources/views/components/damage-diagram). */
    public const ZONES = [
        'front_bumper' => 'Front bumper',
        'bonnet' => 'Bonnet',
        'windscreen' => 'Windscreen',
        'roof' => 'Roof',
        'rear_window' => 'Rear window',
        'boot' => 'Boot',
        'rear_bumper' => 'Rear bumper',
        'left_front' => 'Left front door / wing',
        'left_rear' => 'Left rear door / quarter',
        'right_front' => 'Right front door / wing',
        'right_rear' => 'Right rear door / quarter',
        'wheel_lf' => 'Left front wheel',
        'wheel_lr' => 'Left rear wheel',
        'wheel_rf' => 'Right front wheel',
        'wheel_rr' => 'Right rear wheel',
    ];

    public const DAMAGE_KINDS = [
        'scratch' => 'Scratch',
        'dent' => 'Dent',
        'crack' => 'Crack / broken',
    ];

    protected $fillable = [
        'booking_id', 'car_id', 'type', 'odometer_km', 'fuel_level', 'checklist', 'damages',
        'notes', 'signature_path', 'signed_by_name', 'needs_maintenance', 'performed_by', 'performed_at',
    ];

    protected $casts = [
        'odometer_km' => 'integer',
        'fuel_level' => 'integer',
        'checklist' => 'array',
        'damages' => 'array',
        'needs_maintenance' => 'boolean',
        'performed_at' => 'datetime',
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
    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /** @return HasMany<InspectionPhoto, $this> */
    public function photos(): HasMany
    {
        return $this->hasMany(InspectionPhoto::class);
    }

    public function isPickup(): bool
    {
        return $this->type === self::PICKUP;
    }

    public static function fuelLabel(int $eighths): string
    {
        return match (true) {
            $eighths <= 0 => 'Empty',
            $eighths >= 8 => 'Full',
            $eighths === 4 => '½ tank',
            default => "{$eighths}/8",
        };
    }

    /**
     * Zones whose damage differs from (is worse than) the pickup record.
     *
     * @return array<string, string>
     */
    public function newDamagesComparedTo(?self $pickup): array
    {
        $before = $pickup->damages ?? [];

        return collect($this->damages ?? [])
            ->filter(fn ($kind, $zone) => ($before[$zone] ?? null) !== $kind)
            ->all();
    }
}
