<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Car extends Model
{
    protected $fillable = ['brand', 'model', 'type', 'transmission', 'branch_id'];

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
