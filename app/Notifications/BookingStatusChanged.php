<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * FR-NOT-02 / FR-APR-06. Replaces the commented-out
 * "// $this->sendApprovalNotification($booking);" line that existed (but did
 * nothing) in Admin\BookingController::approve()/reject() — dispatched
 * instead from Booking::approve()/reject() themselves, so every caller gets
 * it automatically: Admin\BookingController, Staff\BookingController, and
 * the Level 2 dashboard's PendingApprovalQueue Livewire component all call
 * those same two model methods.
 */
class BookingStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Booking $booking)
    {
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("Booking #{$this->booking->id} " . ucfirst($this->booking->status) . ' — EasyCar')
            ->greeting("Hi {$notifiable->name},");

        if ($this->booking->isApproved()) {
            $mail->line('Great news — your booking has been approved!')
                ->line('Dates: ' . $this->booking->start_date->format('M d, Y') . ' – ' . $this->booking->end_date->format('M d, Y'));

            if ($this->booking->approval_notes) {
                $mail->line("Note from our team: {$this->booking->approval_notes}");
            }
        } else {
            $mail->line('Unfortunately, your booking could not be approved.')
                ->line("Reason: {$this->booking->rejection_reason}");
        }

        return $mail->action('View Booking', route('bookings.show', $this->booking));
    }

    public function toArray($notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'status' => $this->booking->status,
            'message' => "Your booking is now {$this->booking->status}.",
        ];
    }
}
