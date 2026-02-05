<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\User;
use App\Services\AuditService;
use App\Services\BalanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StripeWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret');

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            Log::warning('Stripe webhook signature verification failed', ['error' => $e->getMessage()]);
            return response('Invalid signature', 400);
        } catch (\Exception $e) {
            Log::warning('Stripe webhook parse error', ['error' => $e->getMessage()]);
            return response('Bad request', 400);
        }

        Log::info('Stripe webhook received', ['type' => $event->type, 'id' => $event->id]);

        return match ($event->type) {
            'checkout.session.completed' => $this->handleCheckoutCompleted($event->data->object),
            'checkout.session.expired'   => $this->handleCheckoutExpired($event->data->object),
            default => response('OK'),
        };
    }

    private function handleCheckoutCompleted($session)
    {
        $payment = Payment::where('gateway_transaction_id', $session->id)->first();

        if (!$payment) {
            Log::warning('Stripe: no payment found for session', ['session_id' => $session->id]);
            return response('Payment not found', 200);
        }

        if ($payment->status === 'completed') {
            return response('Already processed', 200);
        }

        $user = User::find($payment->user_id);

        if (!$user) {
            Log::error('Stripe: user not found', ['user_id' => $payment->user_id]);
            return response('User not found', 200);
        }

        // Credit balance
        $balanceService = app(BalanceService::class);
        $transaction = $balanceService->credit(
            user: $user,
            amount: (string) $payment->amount,
            type: 'topup',
            referenceType: 'payment',
            referenceId: $payment->id,
            description: "Stripe top-up: \${$payment->amount}",
        );

        // Update payment record
        $payment->update([
            'status' => 'completed',
            'completed_at' => now(),
            'transaction_id' => $transaction->id,
            'gateway_response' => json_encode([
                'payment_intent' => $session->payment_intent,
                'payment_status' => $session->payment_status,
                'customer_email' => $session->customer_details?->email,
            ]),
        ]);

        AuditService::logAction('payment.stripe.completed', $payment, [
            'user_id' => $user->id,
            'amount' => $payment->amount,
            'session_id' => $session->id,
        ]);

        Log::info('Stripe payment completed', [
            'payment_id' => $payment->id,
            'user_id' => $user->id,
            'amount' => $payment->amount,
        ]);

        return response('OK');
    }

    private function handleCheckoutExpired($session)
    {
        $payment = Payment::where('gateway_transaction_id', $session->id)->first();

        if ($payment && $payment->status === 'pending') {
            $payment->update([
                'status' => 'failed',
                'notes' => 'Stripe checkout session expired',
            ]);
        }

        return response('OK');
    }
}
