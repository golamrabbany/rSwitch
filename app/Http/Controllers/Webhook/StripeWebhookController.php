<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\PaymentCreditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StripeWebhookController extends Controller
{
    public function handle(Request $request, PaymentCreditService $creditService)
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
            'checkout.session.completed' => $this->handleCheckoutCompleted($event->data->object, $creditService),
            'checkout.session.expired'   => $this->handleCheckoutExpired($event->data->object),
            default => response('OK'),
        };
    }

    private function handleCheckoutCompleted($session, PaymentCreditService $creditService)
    {
        $payment = Payment::where('gateway_transaction_id', $session->id)->first();

        if (!$payment) {
            Log::warning('Stripe: no payment found for session', ['session_id' => $session->id]);
            return response('Payment not found', 200);
        }

        $creditService->creditPayment($payment, [
            'payment_intent' => $session->payment_intent,
            'payment_status' => $session->payment_status,
            'customer_email' => $session->customer_details?->email,
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
