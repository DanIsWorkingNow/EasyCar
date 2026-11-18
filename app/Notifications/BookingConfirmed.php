<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * FR-NOT-01. Dispatched from BookingController::store() right after a
 * booking is created. Queued (ShouldQueue) per FR-NOT-04 — never sent
 * synchronously during the request that created the booking.
 */
class BookingConfirmed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Booking $booking) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $cars = $this->booking->cars->map(fn ($c) => "{$c->brand} {$c->model}")->implode(', ');

        return (new MailMessage)
            ->subject("Booking {$this->booking->reference} received — EasyCar")
            ->greeting("Hi {$notifiable->name},")
            ->line('Thanks for your booking! Here are the details:')
            ->line("Car(s): {$cars}")
            ->line('Dates: '.$this->booking->start_date->format('M d, Y').' – '.$this->booking->end_date->format('M d, Y'))
            ->line('Total: RM '.number_format($this->booking->total_price, 2))
            ->line('Your booking is now pending approval. We will notify you as soon as it is reviewed.')
            ->when(config('payments.enabled') && ! $this->booking->isPaid(), fn ($m) => $m->line(
                'Complete payment within '.config('payments.hold_minutes').' minutes to secure your car.'
            ))
            ->action('View booking', route('bookings.show', $this->booking));
    }

    public function toArray($notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'reference' => $this->booking->reference,
            'status' => 'pending',
            'message' => "Booking {$this->booking->reference} received and pending approval.",
        ];
    }
}
