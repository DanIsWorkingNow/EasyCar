<?php

namespace App\Http\Controllers\Ops;

use App\Enums\ChargeType;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingCharge;
use App\Models\Car;
use App\Models\Payment;
use App\Payments\Exceptions\PaymentException;
use App\Payments\Gateways\CounterGateway;
use App\Services\PaymentService;
use App\Services\Rentals\RentalOperationsService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Extras on a booking: add (damage, cleaning…), waive, and record payment
 * taken at the counter.
 */
class ChargeController extends Controller
{
    public function store(Request $request, Booking $booking, RentalOperationsService $ops)
    {
        $this->authorize('manage-charges', $booking);

        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(ChargeType::manualOptions()))],
            'description' => 'required|string|max:200',
            'amount' => 'required|numeric|min:0.01|max:50000',
            'car_id' => ['nullable', 'integer', Rule::in($booking->cars()->pluck('cars.id'))],
        ]);

        $ops->addCharge(
            $booking,
            isset($data['car_id']) ? Car::find($data['car_id']) : null,
            ChargeType::from($data['type']),
            $data['description'],
            (float) $data['amount'],
            $request->user(),
        );

        return back()->with('success', 'Charge added.');
    }

    public function void(Request $request, BookingCharge $charge, RentalOperationsService $ops)
    {
        $this->authorize('manage-charges', $charge->booking);

        $data = $request->validate(['reason' => 'required|string|max:200']);
        $ops->voidCharge($charge, $data['reason'], $request->user());

        return back()->with('success', 'Charge waived.');
    }

    public function collect(Request $request, Booking $booking, PaymentService $payments)
    {
        $this->authorize('manage-charges', $booking);

        $data = $request->validate([
            'method' => ['required', Rule::in(array_keys(CounterGateway::METHODS))],
            'reference' => 'nullable|string|max:100',
        ]);

        try {
            $payment = $payments->recordCounterPayment($booking, Payment::PURPOSE_CHARGES, $data['method'], $data['reference'] ?? null, $request->user());
        } catch (PaymentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Recorded RM '.number_format($payment->amount, 2).' paid at the counter.');
    }
}
