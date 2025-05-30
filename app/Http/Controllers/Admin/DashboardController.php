<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Car;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        return view('admin.dashboard', [
            'totalCars' => Car::count(),
            'totalBookings' => Booking::count(),
            'totalUsers' => User::count(),
        ]);
    }
}
