<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceIssuedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Invoice $invoice,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $currency = $notifiable->currency ?? 'USD';
        $amount = number_format((float) $this->invoice->total_amount, 2);
        $period = $this->invoice->period_start->format('M j') . ' – ' . $this->invoice->period_end->format('M j, Y');

        return (new MailMessage)
            ->subject("Invoice {$this->invoice->invoice_number} — {$currency} {$amount}")
            ->greeting("Hello {$notifiable->name},")
            ->line("A new invoice has been issued for your account.")
            ->line("**Invoice:** {$this->invoice->invoice_number}")
            ->line("**Period:** {$period}")
            ->line("**Amount:** {$currency} {$amount}")
            ->line("**Due Date:** {$this->invoice->due_date->format('M j, Y')}")
            ->action('View Invoice', url('/dashboard'))
            ->salutation('— rSwitch Billing');
    }
}
