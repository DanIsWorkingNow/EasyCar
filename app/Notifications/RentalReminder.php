<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * FR-NOT-03. Dispatched by the SendRentalReminders scheduled command, not
 * from the Booking model — unlike confirmation/status-change, a reminder
 * isn't triggered by a state transition, it's triggered by the calendar
 * (rental starting or ending soon).
 */
class RentalReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Booking $booking, public string $type)
    {
        // $type is 'pickup' or 'return'
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $cars = $this->booking->cars->map(fn ($c) => "{$c->brand} {$c->model}")->implode(', ');

        if ($this->type === 'pickup') {
            return (new MailMessage)
                ->subject("Reminder: Pick-up Tomorrow — Booking #{$this->booking->id}")
                ->greeting("Hi {$notifiable->name},")
                ->line("This is a reminder that your rental starts tomorrow, {$this->booking->start_date->format('M d, Y')}.")
                ->line("Car(s): {$cars}")
                ->action('View Booking', route('bookings.show', $this->booking));
        }

        return (new MailMessage)
            ->subject("Reminder: Return Tomorrow — Booking #{$this->booking->id}")
            ->greeting("Hi {$notifiable->name},")
            ->line("This is a reminder that your rental is due back tomorrow, {$this->booking->end_date->format('M d, Y')}.")
            ->line("Car(s): {$cars}")
            ->action('View Booking', route('bookings.show', $this->booking));
    }
}
