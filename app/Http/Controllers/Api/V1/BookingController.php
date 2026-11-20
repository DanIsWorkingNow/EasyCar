<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Notifications\BookingConfirmed;
use App\Services\BookingCreationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

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

    public function store(Request $request, BookingCreationService $bookings)
    {
        $validated = $request->validate([
            'start_date' => 'required|date|after_or_equal:'.now()->addDays(2)->toDateString(),
            'end_date' => 'required|date|after:start_date',
            'cars' => 'required|array|min:1|max:2',
            'cars.*' => 'integer|exists:cars,id',
            'accept_terms' => 'sometimes|accepted',
            'addons' => 'sometimes|array',
            'addons.*.id' => 'required|integer|exists:addons,id',
            'addons.*.quantity' => 'required|integer|min:1|max:10',
            'promo_code' => 'sometimes|nullable|string|max:40',
        ]);

        // Availability, the per-customer car limit and pricing all live in
        // BookingCreationService, shared with the web and Livewire flows.
        $booking = $bookings->create(
            $request->user(), $validated['cars'], $validated['start_date'], $validated['end_date'],
            $request->boolean('accept_terms') ? (string) $request->ip() : null,
            collect($validated['addons'] ?? [])->mapWithKeys(fn ($a) => [(int) $a['id'] => (int) $a['quantity']])->all(),
            $validated['promo_code'] ?? null,
        );

        $request->user()->notify(new BookingConfirmed($booking));

        return (new BookingResource($booking->load('promoCode')))->response()->setStatusCode(201);
    }

    public function show(Request $request, Booking $booking)
    {
        $this->authorize('view', $booking);

        return new BookingResource($booking->load(['user', 'cars', 'approvedBy', 'addons', 'promoCode']));
    }

    public function update(Request $request, Booking $booking, BookingCreationService $bookings)
    {
        abort_unless($booking->user_id === $request->user()->id, 403);
        abort_unless($booking->canBeEditedBy($request->user()), 422, 'This booking can no longer be edited.');

        $validated = $request->validate([
            'start_date' => 'required|date|after_or_equal:'.now()->addDays(2)->toDateString(),
            'end_date' => 'required|date|after:start_date',
            'cars' => 'sometimes|array|min:1|max:2',
            'cars.*' => 'integer|exists:cars,id',
        ]);

        // FIX: re-dates the pivot rows and re-prices too — previously only
        // the booking row's dates moved, so availability checks kept using
        // the old rental window and the old price was still charged.
        $bookings->update(
            $booking,
            $validated['cars'] ?? $booking->cars->pluck('id')->all(),
            $validated['start_date'],
            $validated['end_date'],
        );

        return new BookingResource($booking->fresh(['user', 'cars', 'addons', 'promoCode']));
    }

    public function destroy(Request $request, Booking $booking)
    {
        abort_unless($booking->user_id === $request->user()->id, 403);
        abort_unless($booking->canBeCancelledBy($request->user()), 422, 'This booking can no longer be cancelled.');

        // Cancels (and refunds when paid) rather than soft-deleting, so the
        // booking stays in the customer's history and frees the car.
        $booking->cancel($request->user(), $request->input('reason'));

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
        $filename = 'bookings_export_'.now()->format('Ymd_His').'.csv';

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
