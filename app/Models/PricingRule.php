<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A price adjustment. "season" rules apply on dates within starts_on…ends_on;
 * "weekend" rules apply on the listed ISO weekdays (optionally also limited
 * to a date range). Either can be limited to a car type and/or branch.
 */
class PricingRule extends Model
{
    public const SEASON = 'season';

    public const WEEKEND = 'weekend';

    public const DAYS = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'];

    protected $fillable = ['name', 'kind', 'starts_on', 'ends_on', 'days_of_week', 'adjustment_percent', 'car_type', 'branch_id', 'priority', 'is_active'];

    protected $casts = [
        'starts_on' => 'date',
        'ends_on' => 'date',
        'days_of_week' => 'array',
        'adjustment_percent' => 'float',
        'priority' => 'integer',
        'is_active' => 'boolean',
    ];

    /** @return BelongsTo<Branch, $this> */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function appliesTo(Car $car, CarbonInterface $day): bool
    {
        if (! $this->is_active
            || ($this->car_type && $this->car_type !== $car->type)
            || ($this->branch_id && $this->branch_id !== $car->branch_id)
            || ($this->starts_on && $day->lt($this->starts_on))
            || ($this->ends_on && $day->gt($this->ends_on))) {
            return false;
        }

        return $this->kind !== self::WEEKEND
            || in_array($day->isoWeekday(), array_map('intval', $this->days_of_week ?? []), true);
    }

    public function adjustmentLabel(): string
    {
        return ($this->adjustment_percent > 0 ? '+' : '−').rtrim(rtrim(number_format(abs($this->adjustment_percent), 2), '0'), '.').'%';
    }

    public function scopeLabel(): string
    {
        return collect([$this->car_type ? Car::pluralType($this->car_type) : 'All cars', $this->branch?->name])->filter()->implode(' · ');
    }
}
