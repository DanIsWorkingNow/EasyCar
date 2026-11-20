<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Car;
use Illuminate\Support\Facades\Cache;

class LandingController extends Controller
{
    public function __invoke()
    {
        $cars = Cache::remember('landing:cars', 120, fn () => Car::with('branch')->withRatings()
            ->withCount(['bookings as recent_bookings_count' => fn ($q) => $q
                ->where('bookings.created_at', '>=', now()->subDays(30))
                ->whereNotIn('bookings.status', Booking::RELEASED_STATUSES)])
            ->where('status', '!=', 'maintenance')
            ->orderBy('brand')
            ->get());

        $featured = $cars->firstWhere('is_featured', true)
            ?? $cars->sortByDesc('recent_bookings_count')->first();

        // "My List" first for signed-in customers who saved cars.
        $myList = auth()->check() ? $cars->whereIn('id', auth()->user()->favouriteCarIds())->values() : collect();

        $rows = collect([
            ['title' => 'My List', 'cars' => $myList, 'href' => route('favourites.index')],
            ['title' => 'Top 10 this month', 'cars' => $cars->sortByDesc('recent_bookings_count')->take(10)->values(), 'ranked' => true],
            ['title' => 'Family adventures — SUVs & MPVs', 'cars' => $cars->whereIn('type', ['SUV', 'MPV'])->values()],
            ['title' => 'Executive sedans', 'cars' => $cars->where('type', 'Sedan')->values()],
            ['title' => 'City runabouts', 'cars' => $cars->where('type', 'Hatchback')->values()],
            ['title' => 'Under RM 70 a day', 'cars' => $cars->filter(fn ($c) => (float) $c->price_per_day < 70)->sortBy('price_per_day')->values()],
            ['title' => 'Top rated by renters', 'cars' => $cars->filter(fn (Car $c) => (int) $c->getAttribute('rating_count') > 0)->sortByDesc(fn (Car $c) => [(float) $c->getAttribute('rating_avg'), (int) $c->getAttribute('rating_count')])->take(10)->values()],
        ])->filter(fn ($row) => $row['cars']->isNotEmpty())->values();

        return view('welcome', [
            'featured' => $featured,
            'rows' => $rows,
            'branches' => Branch::withCount('cars')->orderBy('name')->get(),
            'stats' => [
                'cars' => $cars->count(),
                'branches' => Branch::count(),
                'trips' => Booking::whereIn('status', ['approved', 'completed'])->count(),
            ],
        ]);
    }
}
