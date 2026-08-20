<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Car;
use App\Notifications\BookingConfirmed;
use App\Services\BookingAvailabilityService;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * TSD Section 8.5. Every write path here goes through the same model
 * methods and services the web controllers use — Booking::approve()/
 * reject() (with the TD-04 re-check and the BookingStatusChanged
 * notification already built in), and BookingAvailabilityService for
 * conflict detection (TD-14). Nothing in this controller re-implements a
 * business rule that already exists elsewhere.
 */
class BookingController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $user = $request->user();
        $query = Booking::with(['user', 'cars']);

        if ($user->hasRole('admin')) {
            if ($request->filled('branch_id')) {
                $query->whereHas('cars', fn ($q) => $q->where('branch_id', $request->branch_id));
            }
        } elseif ($user->hasRole('staff')) {
            $query->whereHas('cars', fn ($q) => $q->where('branch_id', $user->branch_id));
        } else {
            $query->where('user_id', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search') && ! $user->hasRole('customer')) {
            // Portable case-insensitive match (TSD DB-03).
            $search = strtolower($request->search);
            $query->whereHas('user', function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"]);
            });
        }

        return BookingResource::collection($query->orderByDesc('created_at')->paginate(15));
    }

    public function store(Request $request, BookingAvailabilityService $availability)
    {
        $validated = $request->validate([
            'start_date' => 'required|date|after_or_equal:' . now()->addDays(2)->toDateString(),
            'end_date' => 'required|date|after:start_date',
            'cars' => 'required|array|min:1|max:2',
            'cars.*' => 'exists:cars,id',
        ]);

        foreach ($validated['cars'] as $carId) {
            if ($availability->hasConflict($carId, $validated['start_date'], $validated['end_date'])) {
                throw ValidationException::withMessages([
                    'cars' => ['One or more selected cars are not available for these dates.'],
                ]);
            }
        }

        $totalDays = Carbon::parse($validated['start_date'])->diffInDays($validated['end_date']) + 1;

        $booking = DB::transaction(function () use ($request, $validated, $totalDays) {
            $booking = Booking::create([
                'user_id' => $request->user()->id,
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'total_days' => $totalDays,
                'status' => 'pending',
                'total_price' => 0,
            ]);

            $totalPrice = 0;
            foreach ($validated['cars'] as $carId) {
                $car = Car::findOrFail($carId);
                $price = $car->price_per_day * $totalDays;
                $totalPrice += $price;

                $booking->cars()->attach($carId, [
                    'rental_start' => $validated['start_date'],
                    'rental_end' => $validated['end_date'],
                    'quantity' => 1,
                    'price' => $price,
                ]);
            }

            $booking->update(['total_price' => $totalPrice]);

            return $booking;
        });

        $booking->load('cars');

        // Requires Level 3's Notifications class; remove this line if Level 3 isn't applied yet.
        if (class_exists(BookingConfirmed::class)) {
            $request->user()->notify(new BookingConfirmed($booking));
        }

        return (new BookingResource($booking))->response()->setStatusCode(201);
    }

    public function show(Request $request, Booking $booking)
    {
        $this->authorize('view', $booking);

        return new BookingResource($booking->load(['user', 'cars', 'approvedBy']));
    }

    public function update(Request $request, Booking $booking)
    {
        abort_unless($booking->user_id === $request->user()->id, 403);
        abort_unless($booking->isPending() && $booking->start_date->isFuture(), 422, 'This booking can no longer be edited.');

        $validated = $request->validate([
            'start_date' => 'required|date|after_or_equal:' . now()->addDays(2)->toDateString(),
            'end_date' => 'required|date|after:start_date',
        ]);

        $totalDays = Carbon::parse($validated['start_date'])->diffInDays($validated['end_date']) + 1;
        $booking->update(array_merge($validated, ['total_days' => $totalDays]));

        return new BookingResource($booking->fresh(['user', 'cars']));
    }

    public function destroy(Request $request, Booking $booking)
    {
        abort_unless($booking->user_id === $request->user()->id, 403);
        abort_unless($booking->start_date->isFuture(), 422, 'Cannot cancel a booking that has already started.');

        $booking->delete(); // soft delete — see Level 1 kit 1's SoftDeletes fix

        return response()->json(['data' => ['message' => 'Booking cancelled.']]);
    }

    public function approve(Request $request, Booking $booking)
    {
        $this->authorize('approve', $booking);

        $request->validate(['approval_notes' => 'nullable|string|max:500']);

        if (! $booking->approve($request->user(), $request->approval_notes)) {
            return response()->json(['message' => 'Booking could not be approved — it may already be processed or no longer available.'], 422);
        }

        return new BookingResource($booking->fresh(['user', 'cars']));
    }

    public function reject(Request $request, Booking $booking)
    {
        $this->authorize('reject', $booking);

        $request->validate(['rejection_reason' => 'required|string|max:500']);

        if (! $booking->reject($request->user(), $request->rejection_reason)) {
            return response()->json(['message' => 'Booking could not be rejected — it may already be processed.'], 422);
        }

        return new BookingResource($booking->fresh(['user', 'cars']));
    }

    public function bulkApprove(Request $request)
    {
        $request->validate([
            'booking_ids' => 'required|array',
            'booking_ids.*' => 'exists:bookings,id',
        ]);

        $approved = 0;
        $skipped = [];

        foreach ($request->booking_ids as $id) {
            $booking = Booking::find($id);

            if ($booking && Gate::allows('approve', $booking) && $booking->approve($request->user())) {
                $approved++;
            } else {
                $skipped[] = $id;
            }
        }

        return response()->json(['data' => ['approved' => $approved, 'skipped' => $skipped]]);
    }

    public function export(Request $request)
    {
        $this->authorize('viewAny', Booking::class);

        $bookings = Booking::with('user', 'cars')->get();
        $filename = 'bookings_export_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($bookings) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Booking ID', 'Customer Name', 'Start Date', 'End Date', 'Status', 'Car(s)']);

            foreach ($bookings as $booking) {
                $cars = $booking->cars->map(fn ($c) => "{$c->brand} {$c->model}")->implode('; ');
                fputcsv($handle, [
                    $booking->id, $booking->user->name, $booking->start_date,
                    $booking->end_date, $booking->status, $cars,
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
