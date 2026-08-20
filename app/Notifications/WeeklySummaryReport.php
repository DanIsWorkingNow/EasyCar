<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * FR-RPT-03. $summary is a plain array built by SendWeeklySummaryReport —
 * kept as a notification rather than a raw Mailable so it's queued and
 * logged the same way every other notification in this kit is.
 */
class WeeklySummaryReport extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public array $summary)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('EasyCar Weekly Summary — ' . $this->summary['period'])
            ->greeting("Hi {$notifiable->name},")
            ->line('Here is the operational summary for the past week:')
            ->line("Total bookings: {$this->summary['total_bookings']}")
            ->line("Approved: {$this->summary['approved']}")
            ->line("Rejected: {$this->summary['rejected']}")
            ->line("Still pending: {$this->summary['pending']}")
            ->line('Revenue: RM ' . number_format($this->summary['revenue'], 2));

        foreach ($this->summary['by_branch'] as $branch) {
            $mail->line("— {$branch['name']}: {$branch['bookings']} bookings, " . number_format($branch['utilization'] * 100, 1) . '% utilization');
        }

        return $mail->action('Open Dashboard', route('admin.dashboard'));
    }
}
