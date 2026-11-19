<?php

namespace App\Notifications;

use App\Models\VehicleInspection;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PickupCompleted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public VehicleInspection $inspection) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $i = $this->inspection->loadMissing(['booking', 'car']);
        $booking = $i->booking;

        return (new MailMessage)
            ->subject("Enjoy your trip — {$i->car->name} picked up ({$booking->reference})")
            ->greeting("Hi {$notifiable->name},")
            ->line("You've picked up the {$i->car->name} (".$i->car->plate_number.').')
            ->line('Odometer: '.number_format($i->odometer_km).' km · Fuel: '.VehicleInspection::fuelLabel($i->fuel_level))
            ->line('Please return it by '.$booking->returnDueAt()->format('l, d M Y \a\t g:i A').' with the same fuel level.')
            ->line('Late returns are charged per started day at the daily rate.')
            ->action('View handover report', route('inspections.show', $i));
    }

    public function toArray($notifiable): array
    {
        return [
            'booking_id' => $this->inspection->booking_id,
            'inspection_id' => $this->inspection->id,
            'message' => 'Picked up '.$this->inspection->car?->name.' — return by '.$this->inspection->booking?->returnDueAt()->format('d M, g:i A').'.',
        ];
    }
}
