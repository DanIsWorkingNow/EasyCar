<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Car;
use App\Services\BookingAvailabilityService;
use App\Services\ReviewService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CarController extends Controller
{
    public function index(Request $request, BookingAvailabilityService $availability)
    {
        $filters = $request->validate([
            'q' => 'nullable|string|max:100',
            'type' => 'nullable|string|in:'.implode(',', Car::TYPES),
            'transmission' => 'nullable|string|in:'.implode(',', Car::TRANSMISSIONS),
            'branch_id' => 'nullable|integer|exists:branches,id',
            'max_price' => 'nullable|numeric|min:0',
            'sort' => 'nullable|in:recommended,price_asc,price_desc,name',
            'start_date' => 'nullable|date|after_or_equal:today',
            'end_date' => 'nullable|date|after:start_date',
        ]);

        $filtering = collect($filters)->except('sort')->filter(fn ($v) => filled($v))->isNotEmpty();

        $query = Car::with('branch')->withRatings()
            ->search($filters['q'] ?? null)
            ->when($filters['type'] ?? null, fn ($q, $type) => $q->where('type', $type))
            ->when($filters['transmission'] ?? null, fn ($q, $t) => $q->whereRaw('LOWER(transmission) = ?', [strtolower($t)]))
            ->when($filters['branch_id'] ?? null, fn ($q, $id) => $q->where('branch_id', $id))
            ->when($filters['max_price'] ?? null, fn ($q, $max) => $q->where('price_per_day', '<=', $max));

        if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            $query->whereNotIn('id', $availability->unavailableCarIds($filters['start_date'], $filters['end_date']));
        }

        match ($filters['sort'] ?? 'recommended') {
            'price_asc' => $query->orderBy('price_per_day'),
            'price_desc' => $query->orderByDesc('price_per_day'),
            'name' => $query->orderBy('brand')->orderBy('model'),
            default => $query->orderByDesc('is_featured')->orderBy('brand'),
        };

        $cars = $query->get();

        return view('cars.index', [
            'cars' => $cars,
            'filtering' => $filtering,
            'rows' => $filtering ? collect() : $cars->groupBy('type')->sortKeys(),
            'branches' => Branch::orderBy('name')->get(),
            'filters' => $filters,
        ]);
    }

    public function show(Car $car, BookingAvailabilityService $availability, ReviewService $reviewService)
    {
        $car->load('branch');

        $bookedDays = collect($availability->bookedRanges($car->id, 45))
            ->flatMap(function ($range) {
                $days = [];
                for ($d = Carbon::parse($range['start']); $d->lte($range['end']); $d->addDay()) {
                    $days[] = $d->toDateString();
                }

                return $days;
            })
            ->unique()
            ->values();

        $rating = $reviewService->summary($car);
        $reviews = $car->reviews()->published()->with('user')->latest()->limit(12)->get();

        $similar = Car::with('branch')->withRatings()
            ->whereKeyNot($car->id)
            ->where(fn ($q) => $q->where('type', $car->type)->orWhere('branch_id', $car->branch_id))
            ->limit(10)
            ->get();

        return view('cars.show', compact('car', 'bookedDays', 'similar', 'rating', 'reviews'));
    }
}
