<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Payments\Exceptions\PaymentException;
use App\Payments\PaymentManager;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function index(Request $request, PaymentManager $gateways)
    {
        $payments = Payment::with(['booking', 'user'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('gateway'), fn ($q) => $q->where('gateway', $request->gateway))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = Str::lower(trim($request->search));
                $q->where(function ($qq) use ($term) {
                    $qq->whereRaw('LOWER(gateway_reference) LIKE ?', ["%{$term}%"])
                        ->orWhereHas('booking', fn ($b) => $b->whereRaw('LOWER(reference) LIKE ?', ["%{$term}%"]))
                        ->orWhereHas('user', fn ($u) => $u->whereRaw('LOWER(email) LIKE ?', ["%{$term}%"])
                            ->orWhereRaw('LOWER(name) LIKE ?', ["%{$term}%"]));
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $summary = [
            'collected' => (float) Payment::whereIn('status', [PaymentStatus::Paid->value, PaymentStatus::Refunded->value, PaymentStatus::RefundPending->value])->sum('amount'),
            'refunded' => (float) Payment::sum('refunded_amount'),
            'refund_pending' => Payment::where('status', PaymentStatus::RefundPending->value)->count(),
            'month' => (float) Payment::where('status', PaymentStatus::Paid->value)->where('paid_at', '>=', now()->startOfMonth())->sum('amount'),
        ];

        return view('admin.payments.index', [
            'payments' => $payments,
            'summary' => $summary,
            'gatewayOptions' => collect(config('payments.gateways'))->mapWithKeys(fn ($g, $k) => [$k => $g['label'] ?? $k]),
            'configured' => $gateways->available(),
        ]);
    }

    public function show(Payment $payment, PaymentManager $gateways)
    {
        $payment->load(['booking.cars', 'user', 'events' => fn ($q) => $q->latest()]);

        return view('admin.payments.show', [
            'payment' => $payment,
            'supportsRefunds' => $gateways->driver($payment->gateway)->supportsRefunds(),
        ]);
    }

    public function refund(Request $request, Payment $payment, PaymentService $payments)
    {
        $data = $request->validate(['reason' => 'required|string|max:300']);

        try {
            $payments->refund($payment, $data['reason'].' (by '.$request->user()->name.')');
        } catch (PaymentException $e) {
            return back()->with('error', 'Refund could not be completed automatically: '.$e->getMessage().' It has been flagged for manual processing.');
        }

        return back()->with('success', $payment->fresh()->status === PaymentStatus::Refunded
            ? 'Refund issued.'
            : 'This gateway has no refund API — the payment is flagged for a manual refund.');
    }

    public function markRefunded(Request $request, Payment $payment, PaymentService $payments)
    {
        $data = $request->validate(['reference' => 'nullable|string|max:120']);

        try {
            $payments->markRefundCompleted($payment, $data['reference'] ?? null);
        } catch (PaymentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Refund marked as completed.');
    }
}
