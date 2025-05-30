<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
    public function index()
    {
        $branchId = Auth::user()->branch_id;

        $bookings = Booking::whereHas('cars', function ($q) use ($branchId) {
            $q->where('branch_id', $branchId);
        })->with('cars', 'user')->get();

        return view('staff.bookings.index', compact('bookings'));
    }

    public function show(Booking $booking)
    {
        // Optional: Add branch-level access check
        return view('staff.bookings.show', compact('booking'));
    }
}
