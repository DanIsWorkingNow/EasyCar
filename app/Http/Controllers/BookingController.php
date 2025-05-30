<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Car;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class BookingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
         $bookings = \App\Models\Booking::with('cars')
        ->where('user_id', auth()->id())
        ->orderByDesc('created_at')
        ->get();

    return view('bookings.index', compact('bookings'));//
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $cars = Car::all();
    return view('bookings.create', compact('cars'));//
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
{
    $request->validate([
        'start_date' => 'required|date|after:today|after_or_equal:' . now()->addDays(2)->format('Y-m-d'),
        'end_date' => 'required|date|after:start_date',
        'cars' => 'required|array|max:2',
    ]);

    foreach ($request->cars as $car_id) {
        $conflict = DB::table('car_booking')
            ->join('bookings', 'bookings.id', '=', 'car_booking.booking_id')
            ->where('car_id', $car_id)
            ->where(function ($query) use ($request) {
                $query->whereBetween('rental_start', [$request->start_date, $request->end_date])
                      ->orWhereBetween('rental_end', [$request->start_date, $request->end_date])
                      ->orWhere(function ($q) use ($request) {
                          $q->where('rental_start', '<=', $request->start_date)
                            ->where('rental_end', '>=', $request->end_date);
                      });
            })
            ->exists();

        if ($conflict) {
            return back()->withErrors(['cars' => 'Selected car(s) already booked during that time.'])->withInput();
        }
    }

    $booking = Booking::create([
        'user_id' => auth()->id(),
        'start_date' => $request->start_date,
        'end_date' => $request->end_date,
    ]);

    foreach ($request->cars as $car_id) {
        DB::table('car_booking')->insert([
            'booking_id' => $booking->id,
            'car_id' => $car_id,
            'rental_start' => $request->start_date,
            'rental_end' => $request->end_date,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    return redirect()->route('bookings.create')->with('success', 'Booking saved successfully!');

}

    /**
     * Display the specified resource.
     */
    public function show(Booking $booking)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Booking $booking)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Booking $booking)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Booking $booking)
    {
        // Check if the booking belongs to the authenticated user
    if ($booking->user_id !== auth()->id()) {
        abort(403, 'Unauthorized action.');
    }

    $booking->delete(); // Soft delete

    return redirect()->route('bookings.index')->with('success', 'Booking canceled successfully.');//
    }
}
