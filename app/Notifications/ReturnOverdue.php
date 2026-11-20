<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent once to the customer (and, as $forStaff, to the branch team and
 * admins) when a car passes its due time plus grace without coming back.
 */
class ReturnOverdue extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Booking $booking, public bool $forStaff = false) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $b = $this->booking->loadMissing(['user', 'cars.branch']);
        $due = $b->returnDueAt()->format('l, d M Y \a\t g:i A');

        if ($this->forStaff) {
            return (new MailMessage)
                ->subject("Overdue return: {$b->reference} ({$b->car_names})")
                ->greeting("Hi {$notifiable->name},")
                ->line("{$b->car_names} on booking {$b->reference} was due back {$due} and hasn't been returned.")
                ->line("Customer: {$b->user->name} <{$b->user->email}>")
                ->action('Open operations board', route('ops.board'));
        }

        $phone = $b->cars->first()?->branch?->phone;

        return (new MailMessage)
            ->subject("Your EasyCar is overdue — {$b->reference}")
            ->greeting("Hi {$notifiable->name},")
            ->line("Your {$b->car_names} was due back {$due}.")
            ->line('Late returns are charged for each started day at the daily rate. Please return the car as soon as possible'.($phone ? " or call the branch on {$phone}" : '').'.')
            ->action('View booking', route('bookings.show', $b));
    }

    public function toArray($notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'reference' => $this->booking->reference,
            'message' => $this->forStaff
                ? "Overdue return: {$this->booking->reference} ({$this->booking->car_names})."
                : "Your car was due back {$this->booking->returnDueAt()->format('d M, g:i A')} — late fees apply.",
        ];
    }
}
