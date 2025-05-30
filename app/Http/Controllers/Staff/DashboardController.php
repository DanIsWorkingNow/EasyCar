<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Car;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $branchId = Auth::user()->branch_id;

        $cars = Car::where('branch_id', $branchId)->count();

        $bookings = Booking::whereHas('cars', function ($q) use ($branchId) {
            $q->where('branch_id', $branchId);
        })->count();

        return view('staff.dashboard', [
            'totalCars' => $cars,
            'totalBookings' => $bookings,
        ]);
    }
}
