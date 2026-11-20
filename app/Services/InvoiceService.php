<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Issues immutable, gap-free numbered tax invoices (one per settled
 * payment) and credit notes (one per refund). Seller, buyer and lines are
 * snapshotted at issue time. Prices are tax-inclusive; when a tax rate is
 * configured the tax portion is extracted, never added on top.
 */
class InvoiceService
{
    public function issueFor(Payment $payment): ?Invoice
    {
        if (! in_array($payment->status, [PaymentStatus::Paid, PaymentStatus::Refunded, PaymentStatus::RefundPending], true)) {
            return null;
        }

        return $payment->invoices()->where('kind', Invoice::INVOICE)->first()
            ?? $this->create($payment, Invoice::INVOICE, $this->linesFor($payment), (float) $payment->amount);
    }

    public function creditNoteFor(Payment $payment): ?Invoice
    {
        if ($payment->status !== PaymentStatus::Refunded || $payment->refunded_amount <= 0) {
            return null;
        }

        $original = $this->issueFor($payment);

        return $payment->invoices()->where('kind', Invoice::CREDIT_NOTE)->first()
            ?? $this->create($payment, Invoice::CREDIT_NOTE, [[
                'description' => 'Refund against '.$original?->number.($payment->meta['refund_reason'] ?? null ? ' — '.$payment->meta['refund_reason'] : ''),
                'quantity' => 1,
                'unit_amount' => (float) $payment->refunded_amount,
                'amount' => (float) $payment->refunded_amount,
            ]], (float) $payment->refunded_amount, $original);
    }

    public function pdf(Invoice $invoice): \Barryvdh\DomPDF\PDF
    {
        return Pdf::loadView('invoices.pdf', ['invoice' => $invoice->loadMissing('booking', 'original', 'payment')])
            ->setPaper('a4')
            // Embed only the glyphs used (~40 KB instead of the whole font).
            ->setOption(['enable_font_subsetting' => true, 'isRemoteEnabled' => false]);
    }

    /**
     * @return array<int, array{description: string, quantity: float, unit_amount: float, amount: float}>
     */
    private function linesFor(Payment $payment): array
    {
        $payment->loadMissing(['booking.cars', 'booking.addons', 'booking.promoCode', 'charges']);
        $booking = $payment->booking;

        $lines = $payment->isForCharges()
            ? $payment->charges->map(fn ($c) => [
                'description' => $c->type->label().': '.$c->description,
                'quantity' => (float) $c->quantity,
                'unit_amount' => (float) $c->unit_amount,
                'amount' => (float) $c->amount,
            ])->all()
            : $booking->cars->map(function ($car) use ($booking) {
                $price = (float) $car->getRelation('pivot')->getAttribute('price');

                return [
                    'description' => "Car rental — {$car->name} ({$car->plate_number}), ".$booking->start_date->format('d M').' – '.$booking->end_date->format('d M Y'),
                    'quantity' => (float) $booking->total_days,
                    'unit_amount' => round($price / max(1, $booking->total_days), 2),
                    'amount' => $price,
                ];
            })->all();

        if (! $payment->isForCharges()) {
            foreach ($booking->addons as $addon) {
                $lines[] = [
                    'description' => 'Extra — '.$addon->name.($addon->pricing === 'per_day' ? ' ('.$addon->quantity.' × '.$booking->total_days.' days)' : ''),
                    'quantity' => (float) $addon->quantity * ($addon->pricing === 'per_day' ? $booking->total_days : 1),
                    'unit_amount' => (float) $addon->unit_price,
                    'amount' => (float) $addon->amount,
                ];
            }

            if ((float) $booking->discount_amount > 0) {
                $lines[] = [
                    'description' => 'Discount'.($booking->promoCode ? ' — promo code '.$booking->promoCode->code : ''),
                    'quantity' => 1,
                    'unit_amount' => -(float) $booking->discount_amount,
                    'amount' => -(float) $booking->discount_amount,
                ];
            }
        }

        if (! $payment->isForCharges() && (float) $booking->points_discount > 0) {
            $lines[] = [
                'description' => 'EasyCar Rewards — '.number_format($booking->points_redeemed).' points',
                'quantity' => 1,
                'unit_amount' => -(float) $booking->points_discount,
                'amount' => -(float) $booking->points_discount,
            ];
        }

        // The invoice must always equal what was actually paid.
        if (abs(array_sum(array_column($lines, 'amount')) - (float) $payment->amount) > 0.009) {
            $lines = [[
                'description' => ($payment->isForCharges() ? 'Extra charges' : 'Car rental').' — booking '.$booking->reference,
                'quantity' => 1,
                'unit_amount' => (float) $payment->amount,
                'amount' => (float) $payment->amount,
            ]];
        }

        return $lines;
    }

    private function create(Payment $payment, string $kind, array $lines, float $total, ?Invoice $original = null): Invoice
    {
        $rate = (float) config('compliance.invoices.tax_rate', 0);
        $tax = $rate > 0 ? round($total * $rate / (1 + $rate), 2) : 0.0;
        $prefix = $kind === Invoice::CREDIT_NOTE
            ? config('compliance.invoices.credit_note_prefix', 'CN')
            : config('compliance.invoices.invoice_prefix', 'INV');
        $payment->loadMissing('user');

        for ($attempt = 1; ; $attempt++) {
            try {
                return DB::transaction(function () use ($payment, $kind, $lines, $total, $original, $rate, $tax, $prefix) {
                    $year = (int) now()->format('Y');
                    $sequence = (int) Invoice::where('kind', $kind)->where('year', $year)->lockForUpdate()->max('sequence') + 1;

                    return Invoice::create([
                        'kind' => $kind,
                        'year' => $year,
                        'sequence' => $sequence,
                        'number' => sprintf('%s-%d-%06d', $prefix, $year, $sequence),
                        'payment_id' => $payment->id,
                        'booking_id' => $payment->booking_id,
                        'user_id' => $payment->user_id,
                        'original_invoice_id' => $original?->id,
                        'currency' => $payment->currency,
                        'subtotal' => round($total - $tax, 2),
                        'tax_rate' => $rate,
                        'tax_amount' => $tax,
                        'total' => round($total, 2),
                        'lines' => $lines,
                        'seller' => config('compliance.company'),
                        'billed_to' => ['name' => $payment->user?->name, 'email' => $payment->user?->email],
                        'issued_at' => now(),
                    ]);
                });
            } catch (UniqueConstraintViolationException $e) {
                // Another request took this number (or already invoiced the payment).
                if ($existing = $payment->invoices()->where('kind', $kind)->first()) {
                    return $existing;
                }
                if ($attempt >= 3) {
                    throw $e;
                }
            }
        }
    }
}
