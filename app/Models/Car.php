<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Car extends Model
{
    public function bookings()
{
    return $this->belongsToMany(Booking::class)->withPivot('rental_start', 'rental_end');
}

public function branch()
{
    return $this->belongsTo(Branch::class);
}
//
}
