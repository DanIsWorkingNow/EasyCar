<?php

namespace App\Http\Controllers;

use App\Models\Car;
use Illuminate\Http\Request;

/** "My List": a customer's saved cars. */
class FavouriteController extends Controller
{
    public function index(Request $request)
    {
        $cars = $request->user()->favourites()
            ->with('branch')->withRatings()
            ->orderByPivot('created_at', 'desc')
            ->get();

        return view('favourites.index', ['cars' => $cars]);
    }

    public function toggle(Request $request, Car $car)
    {
        $result = $request->user()->favourites()->toggle($car->id);
        $favourited = $result['attached'] !== [];

        if ($request->expectsJson()) {
            return response()->json([
                'favourited' => $favourited,
                'car_id' => $car->id,
                'count' => $request->user()->favourites()->count(),
            ]);
        }

        return back()->with('success', $favourited ? "{$car->name} added to My List." : "{$car->name} removed from My List.");
    }
}
