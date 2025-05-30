<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use SoftDeletes;
    protected $fillable = ['user_id', 'start_date', 'end_date'];
   public function cars()
{
    return $this->belongsToMany(Car::class, 'car_booking')
        ->withPivot('rental_start', 'rental_end');
}

public function user()
{
    return $this->belongsTo(User::class);
}
 //
 
}
