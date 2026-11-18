<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use App\Payments\Exceptions\PaymentException;
use App\Payments\Gateways\SandboxGateway;
use App\Payments\PaymentManager;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    /**
     * Start a hosted checkout for a booking and send the customer to it.
     */
    public function store(Request $request, Booking $booking, PaymentService $payments, PaymentManager $gateways)
    {
        abort_unless($booking->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'gateway' => ['required', 'string', Rule::in(array_keys($gateways->available()))],
        ], ['gateway.in' => 'Please choose an available payment method.']);

        try {
            $payment = $payments->startCheckout($booking, $validated['gateway']);
        } catch (PaymentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->away($payment->checkout_url);
    }

    /**
     * Where gateways send the customer back to after checkout.
     */
    public function return(Request $request, Payment $payment, PaymentService $payments)
    {
        $this->authorize('view', $payment);

        $payment = $payments->handleReturn($payment, $request);

        return view('payments.return', [
            'payment' => $payment->load('booking.cars'),
        ]);
    }

    /**
     * Polled by the return page while a payment is still processing.
     */
    public function status(Request $request, Payment $payment)
    {
        $this->authorize('view', $payment);

        return response()->json([
            'status' => $payment->status->value,
            'label' => $payment->status->label(),
            'pending' => $payment->isPending(),
        ]);
    }

    public function sandbox(Request $request, Payment $payment)
    {
        $this->authorize('view', $payment);
        abort_unless($payment->gateway === 'sandbox' && config('payments.gateways.sandbox.enabled'), 404);

        if (! $payment->isPending()) {
            return redirect()->route('payments.return', $payment);
        }

        return view('payments.sandbox', ['payment' => $payment->load('booking.cars')]);
    }

    public function completeSandbox(Request $request, Payment $payment, PaymentManager $gateways, PaymentService $payments)
    {
        $this->authorize('view', $payment);
        abort_unless($payment->gateway === 'sandbox' && config('payments.gateways.sandbox.enabled'), 404);

        $outcome = $request->validate([
            'outcome' => ['required', Rule::in(SandboxGateway::OUTCOMES)],
        ])['outcome'];

        /** @var SandboxGateway $gateway */
        $gateway = $gateways->driver('sandbox');

        // Exercise the real webhook pipeline (signature -> event log -> state machine).
        $payments->handleWebhook('sandbox', $gateway->simulatedWebhook($payment, $outcome));

        return redirect()->route('payments.return', $payment);
    }
}
