<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * FIX (TD-19): this model had no `use HasFactory;` at all — Car::factory()
 * would fail outright without it. $fillable was also missing 'photo' and
 * 'price_per_day': since Admin\CarController::store() calls
 * Car::create($request->all())/Car::create($validated), every car created
 * through the admin "Add Car" form silently kept price_per_day at its
 * default (0) and no photo, even though the create form has fields for
 * both. Fixed alongside the controller/view changes for the same bug.
 */
class Car extends Model
{
    use HasFactory;

    protected $fillable = ['brand', 'model', 'type', 'transmission', 'branch_id', 'price_per_day', 'photo'];

    protected $casts = [
        'price_per_day' => 'decimal:2',
    ];

    public function bookings()
    {
        return $this->belongsToMany(Booking::class, 'car_booking') // your pivot table name
                    ->withPivot('rental_start', 'rental_end', 'quantity', 'price')
                    ->withTimestamps();
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
