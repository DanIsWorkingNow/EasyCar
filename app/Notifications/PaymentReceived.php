<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReceived extends Notification implements ShouldQueue
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
            ->subject("Payment received for {$booking->reference} — EasyCar")
            ->greeting("Hi {$notifiable->name},")
            ->line('We have received your payment. Here is your receipt:')
            ->line("Booking: {$booking->reference}")
            ->line('Amount: '.$this->payment->currency.' '.number_format($this->payment->amount, 2))
            ->line('Method: '.$this->payment->gatewayLabel())
            ->line('Transaction: '.$this->payment->gateway_reference)
            ->line('Paid at: '.$this->payment->paid_at?->format('M d, Y g:i A'))
            ->action('View booking', route('bookings.show', $booking));
    }

    public function toArray($notifiable): array
    {
        return [
            'booking_id' => $this->payment->booking_id,
            'reference' => $this->payment->booking?->reference,
            'payment' => $this->payment->uuid,
            'message' => 'Payment of '.$this->payment->currency.' '.number_format($this->payment->amount, 2).' received.',
        ];
    }
}
