<?php

namespace App\Notifications;

use App\Models\BookingCharge;
use App\Models\VehicleInspection;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class ReturnCompleted extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  Collection<int, BookingCharge>  $charges  charges raised at this return
     */
    public function __construct(public VehicleInspection $inspection, public Collection $charges) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $i = $this->inspection->loadMissing(['booking', 'car']);

        $mail = (new MailMessage)
            ->subject("Thanks for driving with EasyCar — {$i->car->name} returned ({$i->booking->reference})")
            ->greeting("Hi {$notifiable->name},")
            ->line("We've checked in the {$i->car->name}. Odometer: ".number_format($i->odometer_km).' km · Fuel: '.VehicleInspection::fuelLabel($i->fuel_level).'.');

        if ($this->charges->isEmpty()) {
            $mail->line('No extra charges — thanks for looking after the car!');
        } else {
            $mail->line('The following extra charges were added:');
            foreach ($this->charges as $charge) {
                $mail->line('• '.$charge->description.' — RM '.number_format($charge->amount, 2));
            }
            $mail->line('Total: RM '.number_format($this->charges->sum('amount'), 2).'. You can pay online from your booking page.');
        }

        return $mail->action('View booking', route('bookings.show', $i->booking));
    }

    public function toArray($notifiable): array
    {
        $total = (float) $this->charges->sum('amount');

        return [
            'booking_id' => $this->inspection->booking_id,
            'inspection_id' => $this->inspection->id,
            'message' => 'Returned '.$this->inspection->car?->name.($total > 0 ? ' — RM '.number_format($total, 2).' in extra charges due.' : ' — no extra charges.'),
        ];
    }
}
