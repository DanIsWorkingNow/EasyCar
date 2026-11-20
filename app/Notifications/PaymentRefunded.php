<?php

namespace App\Notifications;

use App\Models\Invoice;
use App\Models\Payment;
use App\Services\InvoiceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentRefunded extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Payment $payment) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $booking = $this->payment->booking;

        return (new MailMessage)
            ->subject("Refund issued for {$booking->reference} — EasyCar")
            ->greeting("Hi {$notifiable->name},")
            ->line('We have refunded '.$this->payment->currency.' '.number_format($this->payment->refunded_amount, 2).' to your original payment method.')
            ->line('Depending on your bank, it can take 5–10 business days to appear on your statement.')
            ->action('View booking', route('bookings.show', $booking))
            ->when($this->document(), fn (MailMessage $m, Invoice $doc) => $m
                ->line('Your '.strtolower($doc->title()).' '.$doc->number.' is attached.')
                ->attachData(app(InvoiceService::class)->pdf($doc)->output(), $doc->filename(), ['mime' => 'application/pdf']));
    }

    private function document(): ?Invoice
    {
        return $this->payment->invoices()->where('kind', Invoice::CREDIT_NOTE)->first();
    }

    public function toArray($notifiable): array
    {
        return [
            'booking_id' => $this->payment->booking_id,
            'reference' => $this->payment->booking?->reference,
            'payment' => $this->payment->uuid,
            'message' => 'Refund of '.$this->payment->currency.' '.number_format($this->payment->refunded_amount, 2).' issued.',
        ];
    }
}
