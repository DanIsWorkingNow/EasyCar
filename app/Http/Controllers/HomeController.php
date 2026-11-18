<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Car;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Post-login landing: staff and admins go to their console; customers
     * get a personal hub (next trip, anything awaiting payment, picks).
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($route = $user->consoleRoute()) {
            return redirect()->route($route);
        }

        $bookings = Booking::with(['cars.branch'])
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        $upcoming = $bookings
            ->filter(fn (Booking $b) => in_array($b->status, ['pending', 'approved'], true) && ! $b->end_date->isPast())
            ->sortBy('start_date');

        return view('home', [
            'nextTrip' => $upcoming->first(),
            'upcoming' => $upcoming->values(),
            'awaitingPayment' => $upcoming->filter(fn (Booking $b) => $b->canBePaid())->values(),
            'tripCount' => $bookings->whereIn('status', ['approved', 'completed'])->count(),
            'notifications' => $user->notifications()->latest()->limit(5)->get(),
            'picks' => Car::with('branch')->inRandomOrder()->limit(10)->get(),
        ]);
    }
}
