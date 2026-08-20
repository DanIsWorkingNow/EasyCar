<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Car;
use App\Models\Branch;
use App\Notifications\BookingConfirmed;
use App\Services\BookingAvailabilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BookingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $bookings = Booking::with(['cars', 'cars.branch'])
                ->where('user_id', auth()->id())
                ->orderByDesc('created_at')
                ->get();

            return view('bookings.index', compact('bookings'));
        } catch (\Exception $e) {
            Log::error('Booking index error: ' . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Unable to load bookings. Please try again.']);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        try {
            // Get all branches for filtering
            $branches = Branch::orderBy('name')->get();
            
            // Start with all cars with their branch relationships
            $carsQuery = Car::with('branch');
            
            // Apply filters if provided
            if ($request->has('branch_id') && $request->branch_id) {
                $carsQuery->where('branch_id', $request->branch_id);
            }
            
            if ($request->has('transmission') && $request->transmission) {
                $carsQuery->where('transmission', $request->transmission);
            }
            
            if ($request->has('type') && $request->type) {
                $carsQuery->where('type', $request->type);
            }
            
            if ($request->has('brand') && $request->brand) {
                $carsQuery->where('brand', 'like', '%' . $request->brand . '%');
            }
            
            // If dates are provided, filter out unavailable cars
            if ($request->has('start_date') && $request->has('end_date')) {
                $unavailableCarIds = app(BookingAvailabilityService::class)
                    ->unavailableCarIds($request->start_date, $request->end_date);

                if (!empty($unavailableCarIds)) {
                    $carsQuery->whereNotIn('id', $unavailableCarIds);
                }
            }
            
            $cars = $carsQuery->orderBy('brand')->orderBy('model')->get();
            
            return view('bookings.create', compact('cars', 'branches'));
        } catch (\Exception $e) {
            Log::error('Booking create form error: ' . $e->getMessage());
            return redirect()->route('bookings.index')->withErrors(['error' => 'Unable to load booking form. Please try again.']);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) 
{
    try {
        $request->validate([
            'start_date' => [
                'required',
                'date',
                'after_or_equal:' . now()->addDays(2)->format('Y-m-d')
            ],
            'end_date' => [
                'required',
                'date',
                'after:start_date'
            ],
            'cars' => [
                'required',
                'array',
                'max:2',
                'min:1'
            ],
            'cars.*' => 'exists:cars,id'
        ], [
            'start_date.after_or_equal' => 'Bookings must be made at least 2 days in advance.',
            'cars.max' => 'You can only book maximum 2 cars per booking.',
            'cars.min' => 'Please select at least 1 car.',
        ]);

        \Log::info('Laravel now(): ' . now());
        \Log::info('Min allowed start_date: ' . now()->addDays(2)->format('Y-m-d'));
        \Log::info('User input start_date: ' . $request->start_date);

        // Check for existing bookings by the same user for the same period
        $existingUserBookings = Booking::where('user_id', auth()->id())
            ->where(function ($query) use ($request) {
                $query->whereBetween('start_date', [$request->start_date, $request->end_date])
                      ->orWhereBetween('end_date', [$request->start_date, $request->end_date])
                      ->orWhere(function ($q) use ($request) {
                          $q->where('start_date', '<=', $request->start_date)
                            ->where('end_date', '>=', $request->end_date);
                      });
            })
            ->with('cars')
            ->get();

        $totalCarsInPeriod = $existingUserBookings->sum(function ($booking) {
            return $booking->cars->count();
        });

        if ($totalCarsInPeriod + count($request->cars) > 2) {
            return back()->withErrors([
                'cars' => 'You can only have maximum 2 cars booked for any overlapping period.'
            ])->withInput();
        }

        // Check for car availability conflicts
        $availability = app(BookingAvailabilityService::class);
        foreach ($request->cars as $car_id) {
            $conflict = $availability->hasConflict($car_id, $request->start_date, $request->end_date);

            if ($conflict) {
                $car = Car::find($car_id);
                return back()->withErrors([
                    'cars' => "The {$car->brand} {$car->model} is already booked during the selected period."
                ])->withInput();
            }
        }

        DB::beginTransaction();

        $totalDays = Carbon::parse($request->start_date)->diffInDays(Carbon::parse($request->end_date)) + 1;

        // Create the booking
        $booking = Booking::create([
            'user_id' => auth()->id(),
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => 'pending', // Default status
            'total_days' => $totalDays,
            'total_price' => 0, // Initialize 0, update after attaching cars
        ]);

        $totalPrice = 0;

        // Attach cars to the booking with price and quantity
        foreach ($request->cars as $car_id) {
            $car = Car::findOrFail($car_id);
            $priceForCar = $car->price_per_day * $totalDays;
            $totalPrice += $priceForCar;

            DB::table('car_booking')->insert([
                'booking_id' => $booking->id,
                'car_id' => $car_id,
                'rental_start' => $request->start_date,
                'rental_end' => $request->end_date,
                'quantity' => 1,
                'price' => $priceForCar,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Update total_price on booking
        $booking->update(['total_price' => $totalPrice]);

        DB::commit();

        $booking->load('cars');
        auth()->user()->notify(new BookingConfirmed($booking));

        return redirect()->route('bookings.index')->with('success',
            'Booking created successfully! Your booking is pending approval. Total price: RM' . number_format($totalPrice, 2));

    } catch (\Exception $e) {
        DB::rollback();
        Log::error('Booking store error: ' . $e->getMessage());
        return back()->withErrors(['error' => 'Something went wrong while creating your booking: ' . $e->getMessage()])->withInput();
    }
}


    /**
     * Display the specified resource.
     */
    public function show(Booking $booking)
    {
        try {
            // Ensure user can only view their own bookings
            if ($booking->user_id !== auth()->id()) {
                abort(403, 'Unauthorized access.');
            }

            $booking->load(['cars', 'cars.branch']);
            return view('bookings.show', compact('booking'));
        } catch (\Exception $e) {
            Log::error('Booking show error: ' . $e->getMessage());
            return redirect()->route('bookings.index')->withErrors(['error' => 'Unable to load booking details. Please try again.']);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Booking $booking)
    {
        try {
            // Ensure user can only edit their own bookings
            if ($booking->user_id !== auth()->id()) {
                abort(403, 'Unauthorized access.');
            }

            // Only allow editing if booking is pending and start date is in future
            if ($booking->status !== 'pending' || Carbon::parse($booking->start_date)->isPast()) {
                return redirect()->route('bookings.index')
                    ->with('error', 'This booking cannot be modified.');
            }

            $branches = Branch::orderBy('name')->get();
            $cars = Car::with('branch')->orderBy('brand')->orderBy('model')->get();
            
            return view('bookings.edit', compact('booking', 'cars', 'branches'));
        } catch (\Exception $e) {
            Log::error('Booking edit form error: ' . $e->getMessage());
            return redirect()->route('bookings.index')->withErrors(['error' => 'Unable to load edit form. Please try again.']);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Booking $booking)
    {
        try {
            // Ensure user can only update their own bookings
            if ($booking->user_id !== auth()->id()) {
                abort(403, 'Unauthorized access.');
            }

            // Only allow updating if booking is pending
            if ($booking->status !== 'pending') {
                return redirect()->route('bookings.index')
                    ->with('error', 'This booking cannot be modified.');
            }

            $request->validate([
    'start_date' => [
        'required',
        'date',
        'after_or_equal:' . now()->addDays(2)->format('Y-m-d'), // Only this rule
    ],
    'end_date' => [
        'required',
        'date',
        'after:start_date',
    ],
    'cars' => [
        'required',
        'array',
        'max:2',
        'min:1',
    ],
    'cars.*' => 'exists:cars,id',
], [
    'start_date.after_or_equal' => 'Bookings must be made at least 2 days in advance.',
    'cars.max' => 'You can only book maximum 2 cars per booking.',
    'cars.min' => 'Please select at least 1 car.',
]);


            // Check for conflicts (excluding current booking)
            $availability = app(BookingAvailabilityService::class);
            foreach ($request->cars as $car_id) {
                $conflict = $availability->hasConflict($car_id, $request->start_date, $request->end_date, $booking->id);

                if ($conflict) {
                    $car = Car::find($car_id);
                    return back()->withErrors([
                        'cars' => "The {$car->brand} {$car->model} is already booked during the selected period."
                    ])->withInput();
                }
            }

            DB::beginTransaction();
            
            // Update booking details
            $booking->update([
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'total_days' => Carbon::parse($request->start_date)->diffInDays(Carbon::parse($request->end_date)) + 1,
            ]);

            // Remove existing car associations
            DB::table('car_booking')->where('booking_id', $booking->id)->delete();

            // Add new car associations
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

            DB::commit();

            return redirect()->route('bookings.index')
                ->with('success', 'Booking updated successfully!');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Booking update error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Something went wrong while updating your booking: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Booking $booking)
    {
        try {
            // Check if the booking belongs to the authenticated user
            if ($booking->user_id !== auth()->id()) {
                abort(403, 'Unauthorized action.');
            }

            // Only allow cancellation if start date is in future
            if (Carbon::parse($booking->start_date)->isPast()) {
                return redirect()->route('bookings.index')
                    ->with('error', 'Cannot cancel past bookings.');
            }

            // Soft delete the booking
            $booking->delete();

            return redirect()->route('bookings.index')
                ->with('success', 'Booking canceled successfully.');
        } catch (\Exception $e) {
            Log::error('Booking destroy error: ' . $e->getMessage());
            return redirect()->route('bookings.index')->withErrors(['error' => 'Unable to cancel booking: ' . $e->getMessage()]);
        }
    }

    /**
     * Get available cars for AJAX requests
     */
    public function getAvailableCars(Request $request)
    {
        try {
            $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after:start_date',
                'branch_id' => 'nullable|exists:branches,id',
                'transmission' => 'nullable|in:automatic,manual',
                'type' => 'nullable|string',
            ]);

            $carsQuery = Car::with('branch');
            
            // Apply filters
            if ($request->branch_id) {
                $carsQuery->where('branch_id', $request->branch_id);
            }
            
            if ($request->transmission) {
                $carsQuery->where('transmission', $request->transmission);
            }
            
            if ($request->type) {
                $carsQuery->where('type', $request->type);
            }

            // Filter out unavailable cars
            $unavailableCarIds = app(BookingAvailabilityService::class)
                ->unavailableCarIds($request->start_date, $request->end_date);

            if (!empty($unavailableCarIds)) {
                $carsQuery->whereNotIn('id', $unavailableCarIds);
            }

            $cars = $carsQuery->get();

            return response()->json([
                'cars' => $cars,
                'count' => $cars->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Get available cars error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Unable to load available cars: ' . $e->getMessage(),
                'cars' => [],
                'count' => 0
            ], 500);
        }
    }
}