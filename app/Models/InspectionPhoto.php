<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InspectionPhoto extends Model
{
    protected $fillable = ['vehicle_inspection_id', 'path'];

    /** @return BelongsTo<VehicleInspection, $this> */
    public function inspection(): BelongsTo
    {
        return $this->belongsTo(VehicleInspection::class, 'vehicle_inspection_id');
    }
}
