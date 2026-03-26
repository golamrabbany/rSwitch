<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\User;
use App\Services\AuditService;
use App\Services\BalanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        $balanceService = app(BalanceService::class);

        // Atomic dual credit: client + reseller in one DB transaction
        DB::transaction(function () use ($user, $payment, $balanceService, $session) {
            $amount = (string) $payment->amount;

            // Step 1: Credit the client
            $clientTxn = $balanceService->credit(
                user: $user,
                amount: $amount,
                type: 'topup',
                referenceType: 'payment',
                referenceId: $payment->id,
                description: "Stripe top-up: \${$amount}",
            );

            // Step 2: Credit the reseller (if client has a reseller parent)
            $resellerTxn = null;
            if ($user->parent_id) {
                $parent = User::find($user->parent_id);
                if ($parent && $parent->isReseller()) {
                    $resellerTxn = $balanceService->credit(
                        user: $parent,
                        amount: $amount,
                        type: 'client_payment',
                        referenceType: 'payment',
                        referenceId: $payment->id,
                        description: "Client payment ({$user->name}): \${$amount}",
                    );
                }
            }

            // Update payment record with both transaction IDs
            $payment->update([
                'status' => 'completed',
                'completed_at' => now(),
                'transaction_id' => $clientTxn->id,
                'reseller_transaction_id' => $resellerTxn?->id,
                'gateway_response' => json_encode([
                    'payment_intent' => $session->payment_intent,
                    'payment_status' => $session->payment_status,
                    'customer_email' => $session->customer_details?->email,
                ]),
            ]);

            AuditService::logAction('payment.stripe.completed', $payment, [
                'user_id' => $user->id,
                'amount' => $payment->amount,
                'reseller_credited' => $resellerTxn ? true : false,
                'session_id' => $session->id,
            ]);
        });

        Log::info('Stripe payment completed', [
            'payment_id' => $payment->id,
            'user_id' => $user->id,
            'amount' => $payment->amount,
            'reseller_credited' => $payment->reseller_transaction_id ? true : false,
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
