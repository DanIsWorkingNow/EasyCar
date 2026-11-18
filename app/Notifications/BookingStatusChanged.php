<?php

namespace App\Notifications;

use App\Enums\BookingPaymentStatus;
use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * FR-NOT-02 / FR-APR-06. Dispatched from Booking::approve()/reject()/
 * cancel() themselves, so every caller (web, API, dashboard widget,
 * scheduled expiry) sends it automatically.
 */
class BookingStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Booking $booking) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $booking = $this->booking;
        $dates = $booking->start_date->format('M d, Y').' – '.$booking->end_date->format('M d, Y');

        $mail = (new MailMessage)
            ->subject("Booking {$booking->reference} ".$booking->statusMeta()['label'].' — EasyCar')
            ->greeting("Hi {$notifiable->name},");

        match ($booking->status) {
            'approved' => $mail->line('Great news — your booking is confirmed!')
                ->line("Dates: {$dates}")
                ->when($booking->approval_notes, fn ($m) => $m->line("Note from our team: {$booking->approval_notes}")),
            'cancelled' => $mail->line('Your booking has been cancelled.')
                ->line("Reason: {$booking->cancellation_reason}"),
            default => $mail->line('Unfortunately, your booking could not be approved.')
                ->line("Reason: {$booking->rejection_reason}"),
        };

        if (in_array($booking->payment_status, [BookingPaymentStatus::Refunded, BookingPaymentStatus::RefundPending], true)) {
            $mail->line('Your payment is being refunded to your original payment method. Bank processing can take 5–10 business days.');
        }

        return $mail->action('View booking', route('bookings.show', $booking));
    }

    public function toArray($notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'reference' => $this->booking->reference,
            'status' => $this->booking->status,
            'message' => "Booking {$this->booking->reference} is now ".strtolower($this->booking->statusMeta()['label']).'.',
        ];
    }
}
