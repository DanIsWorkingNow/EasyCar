<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Car;
use App\Notifications\BookingConfirmed;
use App\Payments\PaymentManager;
use App\Services\BookingAvailabilityService;
use App\Services\BookingCreationService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Customer bookings. Creation normally happens in the Livewire
 * CarAvailabilityPicker (bookings.create); store() remains for plain form
 * posts. Both go through BookingCreationService.
 *
 * Revamp fixes:
 *  - exception messages are no longer echoed back to customers;
 *  - authorization uses BookingPolicy instead of ad-hoc checks;
 *  - update() re-prices the booking (it used to keep the old price and
 *    write pivot rows with no price at all);
 *  - destroy() cancels (and refunds if paid) instead of soft-deleting, so
 *    the booking stays in the customer's history.
 */
class BookingController extends Controller
{
    public function index(Request $request)
    {
        $bookings = Booking::with(['cars.branch', 'latestPayment'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        [$upcoming, $past] = $bookings->partition(
            fn (Booking $b) => in_array($b->status, ['pending', 'approved'], true) && ! $b->end_date->isPast()
        );

        return view('bookings.index', compact('bookings', 'upcoming', 'past'));
    }

    public function create(Request $request)
    {
        return view('bookings.create', [
            'preselectedCarId' => $request->integer('car') ?: null,
        ]);
    }

    public function store(Request $request, BookingCreationService $bookings)
    {
        $validated = $this->validateBooking($request);

        $request->validate(['accept_terms' => 'accepted'], ['accept_terms.accepted' => 'Please accept the Terms of Rental to continue.']);

        $booking = $bookings->create($request->user(), $validated['cars'], $validated['start_date'], $validated['end_date'], (string) $request->ip());

        $request->user()->notify(new BookingConfirmed($booking));

        return redirect()->route('bookings.show', $booking)
            ->with('success', 'Booking '.$booking->reference.' reserved. Total: RM '.number_format($booking->total_price, 2).'.');
    }

    public function show(Request $request, Booking $booking, PaymentManager $gateways)
    {
        $this->authorize('view', $booking);

        $booking->load(['cars.branch', 'payments' => fn ($q) => $q->latest()->with('invoices'), 'inspections', 'charges', 'user.driverProfile', 'addons', 'promoCode', 'reviews', 'deposit']);

        return view('bookings.show', [
            'booking' => $booking,
            'gateways' => ($booking->canBePaid() || $booking->canPayCharges()) ? $gateways->available() : [],
            'defaultGateway' => config('payments.default'),
        ]);
    }

    public function edit(Request $request, Booking $booking)
    {
        if (! $booking->canBeEditedBy($request->user())) {
            abort_unless($booking->user_id === $request->user()->id, 403);

            return redirect()->route('bookings.show', $booking)
                ->with('error', 'This booking can no longer be modified.');
        }

        $booking->load('cars');

        return view('bookings.edit', [
            'booking' => $booking,
            'cars' => Car::with('branch')->orderBy('brand')->orderBy('model')->get(),
            'branches' => Branch::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Booking $booking, BookingCreationService $bookings)
    {
        $this->authorize('update', $booking);

        $validated = $this->validateBooking($request);

        $bookings->update($booking, $validated['cars'], $validated['start_date'], $validated['end_date']);

        return redirect()->route('bookings.show', $booking)
            ->with('success', 'Booking updated. New total: RM '.number_format($booking->fresh()->total_price, 2).'.');
    }

    public function destroy(Request $request, Booking $booking)
    {
        $this->authorize('cancel', $booking);

        $request->validate(['reason' => 'nullable|string|max:500']);

        $wasPaid = $booking->isPaid();
        $booking->cancel($request->user(), $request->input('reason'));

        return redirect()->route('bookings.index')->with('success', $wasPaid
            ? 'Booking cancelled. Your refund is on its way.'
            : 'Booking cancelled.');
    }

    /**
     * JSON availability lookup (kept for backwards compatibility).
     */
    public function getAvailableCars(Request $request, BookingAvailabilityService $availability)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'branch_id' => 'nullable|exists:branches,id',
            'transmission' => 'nullable|string|max:20',
            'type' => 'nullable|string|max:20',
        ]);

        $cars = Car::with('branch')
            ->when($request->branch_id, fn ($q) => $q->where('branch_id', $request->branch_id))
            ->when($request->transmission, fn ($q) => $q->whereRaw('LOWER(transmission) = ?', [strtolower($request->transmission)]))
            ->when($request->type, fn ($q) => $q->where('type', $request->type))
            ->whereNotIn('id', $availability->unavailableCarIds($request->start_date, $request->end_date))
            ->get();

        return response()->json(['cars' => $cars, 'count' => $cars->count()]);
    }

    /**
     * @return array{start_date: string, end_date: string, cars: int[]}
     *
     * @throws ValidationException
     */
    private function validateBooking(Request $request): array
    {
        return $request->validate([
            'start_date' => ['required', 'date', 'after_or_equal:'.now()->addDays(2)->toDateString()],
            'end_date' => ['required', 'date', 'after:start_date'],
            'cars' => ['required', 'array', 'min:1', 'max:'.BookingCreationService::MAX_CARS_PER_PERIOD],
            'cars.*' => ['integer', 'exists:cars,id'],
        ], [
            'start_date.after_or_equal' => 'Bookings must be made at least 2 days in advance.',
            'cars.max' => 'You can book at most '.BookingCreationService::MAX_CARS_PER_PERIOD.' cars per booking.',
            'cars.min' => 'Please select at least one car.',
        ]);
    }
}
