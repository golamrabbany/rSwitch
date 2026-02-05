<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class KycStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private string $status,
        private ?string $reason = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->greeting("Hello {$notifiable->name},");

        if ($this->status === 'approved') {
            $mail->subject('KYC Verification Approved')
                ->line('Your KYC verification has been **approved**.')
                ->line('You now have full access to all platform features.')
                ->action('Go to Dashboard', url('/dashboard'));
        } else {
            $mail->subject('KYC Verification Requires Attention')
                ->line('Your KYC verification has been **rejected**.');

            if ($this->reason) {
                $mail->line("**Reason:** {$this->reason}");
            }

            $mail->line('Please review the feedback and resubmit your documents.')
                ->action('Resubmit KYC', url('/kyc'));
        }

        return $mail->salutation('— rSwitch Team');
    }
}
