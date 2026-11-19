<?php

namespace App\Http\Controllers\Ops;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Car;
use App\Models\VehicleInspection;

class HandoverController extends Controller
{
    public function pickup(Booking $booking, Car $car)
    {
        return $this->form($booking, $car, VehicleInspection::PICKUP);
    }

    public function return(Booking $booking, Car $car)
    {
        return $this->form($booking, $car, VehicleInspection::RETURN);
    }

    private function form(Booking $booking, Car $car, string $type)
    {
        $this->authorize('handover', $car);
        abort_unless($booking->cars()->whereKey($car->id)->exists(), 404);

        return view('ops.handover', compact('booking', 'car', 'type'));
    }
}
