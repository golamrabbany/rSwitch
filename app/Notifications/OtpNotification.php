<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OtpNotification extends Notification
{
    use Queueable;

    public function __construct(private string $otp)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Login OTP - ' . config('app.name'))
            ->greeting('Hello ' . ($notifiable->name ?? 'User') . ',')
            ->line('Your one-time password (OTP) for login is:')
            ->line('**' . $this->otp . '**')
            ->line('This code will expire in 5 minutes.')
            ->line('If you did not request this code, please ignore this email.')
            ->salutation('— ' . config('app.name'));
    }
}
