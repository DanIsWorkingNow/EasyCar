<?php

namespace App\Observers;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\InvoiceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Every way a payment can settle (gateway webhook, browser return, counter,
 * seeding) ends in a save, so invoicing hangs off the model rather than
 * each code path. Runs after commit and never breaks payment processing.
 */
class PaymentObserver
{
    public function saved(Payment $payment): void
    {
        if (! in_array($payment->status, [PaymentStatus::Paid, PaymentStatus::Refunded, PaymentStatus::RefundPending], true)) {
            return;
        }

        DB::afterCommit(function () use ($payment) {
            try {
                $invoices = app(InvoiceService::class);
                $invoices->issueFor($payment);

                if ($payment->status === PaymentStatus::Refunded) {
                    $invoices->creditNoteFor($payment);
                }
            } catch (\Throwable $e) {
                Log::error('Invoice could not be issued', ['payment' => $payment->uuid, 'error' => $e->getMessage()]);
            }
        });
    }
}
