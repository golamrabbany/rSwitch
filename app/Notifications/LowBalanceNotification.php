<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowBalanceNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private string $currentBalance,
        private string $threshold,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $currency = $notifiable->currency ?? 'USD';

        return (new MailMessage)
            ->subject("Low Balance Alert — {$currency} {$this->currentBalance}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your account balance has fallen below your configured threshold.")
            ->line("**Current Balance:** {$currency} {$this->currentBalance}")
            ->line("**Threshold:** {$currency} {$this->threshold}")
            ->line("Please top up your account to avoid service interruptions.")
            ->action('View Dashboard', url('/dashboard'))
            ->salutation('— rSwitch Billing');
    }
}
