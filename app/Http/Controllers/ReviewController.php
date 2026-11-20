<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Car;
use App\Services\ReviewService;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request, Booking $booking, ReviewService $reviews)
    {
        abort_unless($booking->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'car_id' => 'required|integer|exists:cars,id',
            'rating' => 'required|integer|between:1,5',
            'title' => 'nullable|string|max:120',
            'body' => 'nullable|string|max:2000',
        ], ['rating.required' => 'Choose a star rating.']);

        $car = Car::findOrFail($data['car_id']);
        $reviews->submit($booking, $car, $request->user(), $data);

        return redirect()->route('bookings.show', $booking)->withFragment('reviews')
            ->with('success', "Thanks — your review of the {$car->name} is live.");
    }
}
