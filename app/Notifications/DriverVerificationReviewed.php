<?php

namespace App\Notifications;

use App\Models\DriverProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DriverVerificationReviewed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public DriverProfile $profile) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $mail = (new MailMessage)->greeting("Hi {$notifiable->name},");

        if ($this->profile->isVerified()) {
            return $mail->subject('You’re verified to drive with EasyCar')
                ->line('Thanks — your driving licence and ID have been verified.')
                ->line('Your bookings can now be confirmed, and pick-up will be quicker at the counter.')
                ->action('View my bookings', route('bookings.index'));
        }

        return $mail->subject('Action needed: driver verification')
            ->line('We couldn’t verify your documents yet:')
            ->line('“'.$this->profile->rejection_reason.'”')
            ->line('Please upload clear photos of your documents again.')
            ->action('Update my documents', route('verification.show'));
    }

    public function toArray($notifiable): array
    {
        return [
            'message' => $this->profile->isVerified()
                ? 'Your driving licence has been verified.'
                : 'Driver verification needs attention: '.$this->profile->rejection_reason,
        ];
    }
}
